# docs/AGENTS.md

How documentation in this repository is organised and written. The root `AGENTS.md` covers commands, architecture and the rules an agent must not break; this file covers where every other piece of documentation goes. It never lists the docs themselves: the folders are the index.

## Structure

```
AGENTS.md                    Agent guide: commands, architecture, must-not-break rules.
docs/
  AGENTS.md                  This file: folder structure, what goes where, writing rules.
  <topic>.md                 How one feature or integration works.
  runbooks/<topic>.md        Step-by-step operating procedures.
  services/<name>/AGENTS.md  Notes on one external service (entry file), more files only when it gets long.
  security/<topic>.md        How a surface is protected.
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
- `research/`: dated, with sources. Dated audits and analyses go here too.
- `brand/`: logos and brand files.
- `playground/` (repo root, untracked): plans, specs, evidence, prototypes and scratch work. Never committed; what a finished plan decides goes into the doc of the feature it changes.
- A README that documents one package stays beside its code (`src/<Package>/README.md`). The split `octoberrain/*` packages ship theirs.
- This repository is public. `security/` and `research/` never describe an unfixed vulnerability; report those through October's security policy.

## Writing rules

- English.
- Open with one paragraph on what the doc covers.
- Name files, routes, tables and env vars exactly as they appear in the code.
- Date anything measured.
- Never write a live secret, token or session into a doc. Name where it lives instead.
- Update the doc in the same PR as the code it describes.
