# Prompt Guideline

## Purpose

Prompts are the smallest unit of work in the epic → story → prompt hierarchy. Each prompt is a focused, self-contained instruction for AI implementation. A story typically has 1-5 prompts.

## Writing Process

### Step 1: Define the Prompt

Write the top sections (Context through Acceptance Criteria) based on the story overview and your understanding of what needs to happen. Focus on the what and why.

### Step 2: Tech Analysis

Research the codebase to understand existing patterns, conventions, and the specific files involved. Read the code that the prompt will build on or extend. Adjust the prompt sections based on what you find -- the Constraints section especially benefits from pointing at real patterns.

### Step 3: Fill in Tech Analysis, References & Files

After researching the codebase, add the sections below the `---` line. Tech Analysis captures interpreted findings. References and Files are the concrete implementation details derived from those findings.

## Sections

### Context

Current state of the codebase relevant to this prompt. What exists, what prior prompts accomplished, and any background the AI needs. Keep it focused -- only include what's relevant to this specific prompt.

### Goal

One or two sentences. What does this prompt accomplish? Be specific and concrete. Avoid vague language like "set up" or "improve" without saying exactly what the end state is.

### Non-Goals

What this prompt should NOT do. Prevents over-engineering, scope creep, and unnecessary refactoring. Be explicit -- if there's a tempting adjacent task, call it out here.

### Constraints

Patterns to follow, conventions to match, technical requirements. This section has the biggest impact on AI output quality. Point at existing code as examples whenever possible. "Follow the pattern in `ColorwayController`" is better than describing the pattern from scratch.

### Acceptance Criteria

Testable conditions for "done." Prefer concrete checks: commands to run, expected outputs, specific behaviors to verify. Avoid subjective criteria like "clean code" or "well-structured."

---

### Tech Analysis

Summarize findings from the codebase research that affect implementation. Focus on interpreted insights, not raw observations. Things like: what patterns exist, what's missing, what's unexpected, and decisions made because of what was found. This tells the AI *why* the References and Files sections look the way they do.

### References

Files the AI should read before writing any code. These establish the patterns and conventions to follow. List the file path and a brief note on what to look for in that file.

### Files

Specific file paths to create or modify, with a brief note on what changes. This reduces guesswork and prevents files from being created in wrong locations.

## File Naming

Each prompt file is named with its order number and a short descriptive slug:

```
1-short-name.md
2-another-name.md
```

The number sets execution order within the story. The slug should be 2-4 words, lowercase, hyphenated. Examples: `1-configure-sanctum.md`, `2-token-command-tests.md`.

## Status Tracking

Every prompt has a `status` line as the very first line of the file:

```
status: pending
```

Two values: `pending` or `done`. Update to `done` after the prompt is fully implemented and acceptance criteria are met.

## Skeleton

```markdown
status: pending

# Story X.Y: Prompt N -- [Short Name]

## Context

[Current state. What exists. What prior prompts accomplished.]

## Goal

[One or two sentences. Specific end state.]

## Non-Goals

- [What NOT to do]

## Constraints

- [Patterns to follow]
- [Conventions to match]

## Acceptance Criteria

- [ ] [Testable condition]
- [ ] [Command to run and expected result]

---

## Tech Analysis

[Findings from codebase research. What patterns exist, what's missing,
what decisions were made based on what was found.]

## References

- `path/to/file.php` -- [what to look for]

## Files

- Create `path/to/new/file.php` -- [what it does]
- Modify `path/to/existing/file.php` -- [what changes]
```
