# docs/AGENTS.md

How documentation in this repository is organised and written. The root `AGENTS.md` covers commands, architecture and the rules an agent must not break; this file covers where every other piece of documentation goes. It never lists the docs themselves: the folders are the index.

## Structure

```
AGENTS.md                    Agent guide: commands, architecture, must-not-break rules.
docs/
  AGENTS.md                  This file: folder structure, what goes where, ADR rules, writing rules.
  <topic>.md                 How one feature or integration works.
  runbooks/<topic>.md        Step-by-step operating procedures.
  services/<name>/AGENTS.md  Notes on one external service (entry file), more files only when it gets long.
  security/<topic>.md        How a surface is protected.
  decisions/NNNN-<topic>.md  Architecture decision records (ADRs): why something is built the way it is.
  research/<topic>.md        Competitor, market or technology research.
  brand/                     Logos and brand assets.
playground/                  Local scratch space, ignored by git.
```

Folders exist only when something is in them (git does not track empty folders).

## What goes where

- `<topic>.md`: one feature per file, kebab-case name. Its contract, its data (tables, columns, routes, jobs) and its failure modes.
- `runbooks/`: a procedure someone repeats. Numbered steps, exact commands, and how to verify each step.
- `services/<name>/`: what the project relies on from an external service (what it is used for, auth, endpoints, limits, quirks). Tag each claim as verified (date and source) or inferred. Start with `AGENTS.md`; add sibling files only when it gets long.
- `security/`: what each defense stops, what it leaves to another layer, and where that layer lives.
- `decisions/`: architecture decision records, see below.
- `research/`: dated, with sources. Dated audits and analyses go here too.
- `brand/`: logos and brand files.
- `playground/` (repo root, untracked): plans, specs, evidence, prototypes and scratch work. Never committed; a plan that reaches a conclusion is recorded as an ADR.
- A README that documents one package stays beside its code (`src/<Package>/README.md`). The split `octoberrain/*` packages ship theirs.
- This repository is public. `security/` and `research/` never describe an unfixed vulnerability; report those through October's security policy.

## Decisions (ADRs)

An ADR records one decision: what was decided, the alternatives that lost and the cost accepted, so nobody re-derives the reasoning or re-proposes a rejected option. It is not a plan or a spec (those are working documents and go in `playground/`); design detail belongs only as far as it explains the choice.

- Take the next free number; file `NNNN-<topic>.md`, title `# ADR-NNNN: Short title`. Commit it with the work it explains.
- An accepted ADR is immutable. To change a decision, write a new ADR and set the old one's status to `Superseded by ADR-NNNN`. When the new ADR replaces only part of the old one, the old one stays `Accepted` and the new one names the part it changes.
- Every ADR has these headings: Status (`Proposed`, `Accepted`, or `Superseded by ADR-NNNN`), Date, Deciders, Context, Decision, Consequences, Alternatives considered (each option and why it lost), When to reconsider.
- Deciders names who made the decision (name and role, for example "Jane Doe (CTO)"), taken from the PR, the meeting notes or the commit author. Write `Unknown` when no source says, never guess.
- No index table of ADRs; the numbered file names are the index.
- UX and UI decisions count too when real alternatives were weighed, the change is costly to undo (many screens or habits), or the rejected option is likely to come back. Routine tweaks (colors, spacing, copy, layout fixes) stay in the commit and the feature doc.
- A spec that code still cites by path stays in `decisions/` as `YYYY-MM-DD-<topic>-design.md` until its decision is captured in an ADR.

## Writing rules

- English.
- Open with one paragraph on what the doc covers.
- Name files, routes, tables and env vars exactly as they appear in the code.
- Date anything measured.
- Never write a live secret, token or session into a doc. Name where it lives instead.
- Update the doc in the same PR as the code it describes.
