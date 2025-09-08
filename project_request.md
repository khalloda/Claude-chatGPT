# Project Implementation and Documentation Management

I need you to start implementing the project using the planning documents we've created, with a structured approval and documentation update process.

## Implementation Process

### Phase 1: Initial Setup and Priority Assessment
1. **Read and understand** the current project state from:
   - `COMPREHENSIVE_PROJECT_ANALYSIS.md`
   - `PRD.md`
   - `Plan.md` 
   - `Tasks.md`

2. **Present the first implementation proposal**:
   - Identify the first task to implement (highest priority P0 task)
   - Explain what you plan to do and why
   - Show the expected changes and their impact
   - Estimate the effort and timeline
   - Wait for my approval before proceeding

### Phase 2: Iterative Implementation Workflow

For each task implementation, follow this exact workflow:

#### Step A: Task Proposal
Before implementing anything:
1. **Select the next task** from Tasks.md based on:
   - Priority level (P0 → P1 → P2 → P3)
   - Dependency completion
   - Logical implementation sequence

2. **Present the implementation plan**:
   - Task ID and description from Tasks.md
   - Specific files that will be modified/created
   - Code changes you plan to make
   - Expected impact on the project
   - Testing approach for verification
   - Dependencies and prerequisites

3. **Wait for explicit approval** with statement like "Approved, proceed" before making any changes

#### Step B: Implementation
After receiving approval:
1. **Implement the changes** as planned
2. **Test the implementation** according to the testing requirements
3. **Verify all acceptance criteria** are met
4. **Document any issues** or deviations from the plan

#### Step C: Completion Report
After each implementation:
1. **Report what was completed**:
   - Task ID and summary of changes made
   - Files modified/created with brief descriptions
   - Any issues encountered and how they were resolved
   - Testing results and verification status
   - Impact on other parts of the project

2. **Wait for approval** of the completed work before proceeding to updates

#### Step D: Documentation Updates
After approval of completed work:
1. **Update Tasks.md**:
   - Mark completed task as ✅ COMPLETED
   - Update status of dependent tasks
   - Add any new tasks discovered during implementation
   - Update effort estimates based on actual time taken

2. **Update Plan.md**:
   - Update implementation progress
   - Adjust timelines if necessary
   - Note any risks that materialized or were mitigated
   - Update the current phase status

3. **Update PRD.md** (if requirements changed):
   - Add any new requirements discovered
   - Modify existing requirements if needed
   - Update success metrics if applicable

4. **Update COMPREHENSIVE_PROJECT_ANALYSIS.md**:
   - Mark addressed issues as resolved
   - Update the project status section
   - Add notes about implementation decisions
   - Document lessons learned

5. **Update project documentation**:
   - README.md with new features or setup changes
   - API documentation for new endpoints
   - Code comments and docstrings
   - Configuration documentation
   - Deployment guides if changed

## Communication Protocol

### For Task Proposals:
Always present in this format:
```
## Next Task Proposal

**Task ID**: [from Tasks.md]
**Priority**: [P0/P1/P2/P3]
**Title**: [task title]

**What I plan to do**:
- [detailed explanation]

**Files to be modified**:
- [list of files with brief description of changes]

**Expected impact**:
- [how this affects the project]

**Testing approach**:
- [how I'll verify it works]

**Dependencies**:
- [any prerequisites or blockers]

**Database impact** (if applicable):
- [how this affects the database schema or data]
- [migration strategy if needed]

**Approval needed**: Please respond with "Approved, proceed" or provide feedback for modifications.
```

### For Completion Reports:
Always present in this format:
```
## Task Completion Report

**Task ID**: [completed task ID]
**Status**: ✅ COMPLETED

**Changes made**:
- [summary of what was implemented]

**Files modified/created**:
- [list with brief descriptions]

**Testing results**:
- [verification outcomes]

**Issues encountered**:
- [any problems and solutions]

**Next steps**: Ready to update documentation upon your approval.
```

## Quality Assurance

For each implementation:
1. **Follow existing code patterns** and standards
2. **Maintain backward compatibility** unless explicitly changing it
3. **Include proper error handling** and logging
4. **Add appropriate tests** for new functionality
5. **Update documentation** to reflect changes
6. **Verify no regressions** in existing functionality

## Progress Tracking

Maintain a running progress summary:
- **Completed tasks**: Count and percentage
- **Current phase**: Which part of Plan.md we're in
- **Blockers**: Any issues preventing progress
- **Timeline**: Actual vs. planned progress

## Emergency Protocols

If critical issues are discovered during implementation:
1. **Stop immediately** and report the issue
2. **Assess the impact** on the overall project
3. **Propose mitigation strategies**
4. **Wait for direction** before proceeding
5. **Update risk documentation** accordingly

## Success Criteria

This process succeeds when:
1. **All tasks are completed** according to specifications
2. **Documentation stays current** with all changes
3. **Quality standards are maintained** throughout
4. **Approval workflow** is followed consistently
5. **Project evolution** is properly tracked and documented

---

**Initial Request**: Please start by reading all the planning documents and presenting your first task proposal following the format above. Do not implement anything until I give explicit approval.