import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import { parse } from 'yaml'

// The aggregate `CI` job is the one check a ruleset requires from `ci.yml`, so a job missing from
// its `needs:` list is required by nothing while reading as a gate (#17). The same drift is caught
// in CI by `aggregate-needs`; this test runs it with `npm run test:scripts` as well, and carries
// the control that shows the comparison can fail.
const AGGREGATE = 'ci'

/**
 * The jobs of a workflow that the aggregate does not need, and the needs entries naming no job.
 *
 * @param {string} source The workflow's YAML.
 * @returns {{missing: string[], unknown: string[]}} Job ids absent from `needs:`; entries that name no job.
 */
export function aggregateDrift(source) {
    const jobs = parse(source)?.jobs
    if (jobs === null || typeof jobs !== 'object' || !(AGGREGATE in jobs)) {
        throw new Error(`workflow has no ${AGGREGATE} aggregate job`)
    }

    const needs = [jobs[AGGREGATE].needs ?? []].flat()
    const others = Object.keys(jobs).filter((id) => id !== AGGREGATE)

    return {
        missing: others.filter((id) => !needs.includes(id)),
        unknown: needs.filter((id) => !others.includes(id)),
    }
}

describe('the CI aggregate job', () => {
    it('is named exactly CI', () => {
        const jobs = parse(readFileSync(join(import.meta.dirname, '..', '.github', 'workflows', 'ci.yml'), 'utf8')).jobs

        expect(jobs[AGGREGATE].name).toBe('CI')
    })

    it('needs every other job in ci.yml and no job that does not exist', () => {
        const source = readFileSync(join(import.meta.dirname, '..', '.github', 'workflows', 'ci.yml'), 'utf8')

        expect(aggregateDrift(source)).toEqual({ missing: [], unknown: [] })
    })

    it('names a job left out of needs', () => {
        const source = ['jobs:', '  a:', '    runs-on: x', '  b:', '    runs-on: x', '  ci:', '    needs: [a]', ''].join('\n')

        expect(aggregateDrift(source)).toEqual({ missing: ['b'], unknown: [] })
    })

    it('names a needs entry that is no job', () => {
        const source = ['jobs:', '  a:', '    runs-on: x', '  ci:', '    needs: [a, gone]', ''].join('\n')

        expect(aggregateDrift(source)).toEqual({ missing: [], unknown: ['gone'] })
    })

    it('refuses a workflow with no aggregate, which would otherwise read as covered', () => {
        expect(() => aggregateDrift('jobs:\n  a:\n    runs-on: x\n')).toThrow('no ci aggregate job')
    })
})
