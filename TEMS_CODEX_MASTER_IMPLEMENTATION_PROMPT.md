# TEMS CODEX MASTER IMPLEMENTATION PROMPT

## Texaro Enterprise Management System

### Master Enterprise Architecture, System Improvement and Development Instruction

You are working on the **Texaro Enterprise Management System**, abbreviated as **TEMS**, an internal enterprise operating system for **Texaro Technologies Limited**.

Your responsibility is to inspect the existing TEMS codebase, understand what has already been implemented, preserve all functional work, identify architectural and functional gaps, and progressively transform the system into a complete, integrated, secure and intelligent enterprise management platform.

Do not treat TEMS as a collection of unrelated CRUD modules.

TEMS must operate as one connected company operating system in which strategy, people, products, customers, activities, projects, finances, performance, governance and intelligence are connected through verified business workflows.

---

# 1. PRIMARY ASSIGNMENT

Perform the following work:

1. Inspect the entire existing codebase.
2. Identify the current framework, architecture, modules, models, controllers, services, routes, views, APIs, migrations, permissions, events, queues and tests.
3. Identify which TEMS capabilities are already complete, partially complete or missing.
4. Preserve all working functionality.
5. Refactor only where necessary.
6. Add missing capabilities without unnecessarily rebuilding the system.
7. Connect existing modules through proper workflows, data relationships and domain events.
8. improve the user interface so that every role has a practical working environment.
9. Implement auditability, permissions, approvals, notifications and reporting.
10. Prepare the system for future company intelligence and AI-assisted decision support.

Before changing major functionality, produce an internal implementation analysis containing:

* Current architecture summary.
* Existing modules.
* Existing database entities.
* Existing workflows.
* Existing roles and permissions.
* Functional gaps.
* Architectural risks.
* Recommended implementation order.
* Features that should be preserved.
* Features that require refactoring.
* Features that should be added.

Do not begin by deleting existing features or rewriting the application from zero.

---

# 2. SYSTEM IDENTITY

System name:

**Texaro Enterprise Management System**

System abbreviation:

**TEMS**

Organization:

**Texaro Technologies Limited**

System purpose:

TEMS is the internal enterprise operating system through which Texaro Technologies Limited plans, manages, executes, monitors and improves its business.

The system must support the complete organizational lifecycle:

```text
Executive Strategy
→ Enterprise Planning
→ Organizational Accountability
→ Product and Market Development
→ Marketing
→ Commercial Engagement
→ Customer Acquisition
→ Contracting
→ Billing and Payment
→ Project Implementation
→ Engineering and Product Delivery
→ Customer Success
→ Performance Measurement
→ Enterprise Intelligence
→ Executive and Board Decision-Making
→ Strategy Improvement
```

---

# 3. CORE OPERATING PRINCIPLE

Every important action in TEMS must answer the following questions:

1. Who owns this activity?
2. Which department or position is responsible?
3. Which strategy, target, workplan, project, customer or product does it support?
4. What evidence proves that the activity occurred?
5. Who reviewed or approved it?
6. What business result did it create?
7. Which module should receive the result?
8. How does the result affect company performance?

The system must prioritize verified enterprise activity over self-declared completion.

Where possible, performance evidence should be automatically obtained from completed system transactions.

Examples:

* A completed and verified site visit becomes commercial performance evidence.
* An approved quotation becomes commercial progress evidence.
* A paid invoice becomes revenue performance evidence.
* A completed project milestone becomes implementation performance evidence.
* A successful software release becomes engineering performance evidence.
* A completed customer training becomes customer-success evidence.
* A renewed subscription becomes customer-retention evidence.

---

# 4. SYSTEM DESIGN PRINCIPLES

Implement TEMS according to the following principles.

## 4.1 Modular but integrated

Each module must have a clear business responsibility, but modules must exchange information through controlled relationships and events.

## 4.2 Single source of truth

Important entities must not be unnecessarily duplicated.

For example:

* An organization should not be recreated separately in Commercial Operations, Finance and Projects.
* A customer should have one enterprise account used across all relevant modules.
* An employee should have one HR profile linked to their user account.
* A product should have one product record used by Commercial Operations, Engineering, Finance and Customer Success.

## 4.3 End-to-end traceability

The system must allow management to trace:

```text
Strategic Objective
→ Target
→ Department
→ Employee
→ Activity
→ Evidence
→ Approval
→ Result
→ Revenue or Operational Impact
```

It must also allow traceability of the customer journey:

```text
Campaign
→ Lead
→ Organization
→ Stakeholder
→ Opportunity
→ Discovery
→ Proposal
→ Quotation
→ Contract
→ Invoice
→ Payment
→ Project
→ Implementation
→ Support
→ Renewal
```

## 4.4 Role-based access

Users must only access information and actions that correspond to their responsibilities.

## 4.5 Approval-based control

Sensitive actions must use controlled approval workflows.

## 4.6 Auditability

Important actions, changes, approvals, deletions and status transitions must be recorded.

## 4.7 Evidence-based performance

Performance must be supported by evidence or verified system transactions.

## 4.8 Configuration over hard-coding

Company information, financial settings, approval limits, document numbering, branding, notification preferences and other operational rules should be configurable.

## 4.9 Progressive implementation

Do not attempt to activate every advanced capability simultaneously.

Build stable foundations first and extend the system in controlled phases.

## 4.10 Preserve data integrity

Use foreign keys, validation, database transactions, unique constraints and clear ownership rules.

---

# 5. REQUIRED TEMS MODULE ARCHITECTURE

TEMS should ultimately contain the following modules.

1. Executive Office and Strategy
2. HR and Organization
3. Planning and Performance
4. Marketing and Communications
5. Commercial Operations
6. CRM and Customer Accounts
7. Product Management
8. Engineering and Software Development
9. Projects and Implementation
10. Customer Success and Support
11. Finance and Administration
12. Procurement, Assets and Inventory
13. Legal and Compliance
14. Board and Governance
15. Knowledge and Document Management
16. Company Intelligence Engine
17. Reports and Analytics
18. Users, Roles and Security
19. System Configuration

These modules may be implemented as domain modules, service boundaries or organized application areas depending on the existing codebase.

Do not create unnecessary duplication merely to satisfy this list.

---

# 6. MODULE ONE: EXECUTIVE OFFICE AND STRATEGY

## Purpose

Provide the highest-level strategic direction and management oversight for the company.

## Required capabilities

* Company vision.
* Company mission.
* Core values.
* Annual company theme.
* Strategic pillars.
* Strategic objectives.
* Executive priorities.
* Strategic initiatives.
* CEO directives.
* Executive decisions.
* Management meeting records.
* Executive action tracker.
* Corporate risk register.
* Executive briefing notes.
* Strategy review history.
* Escalations.
* Major decision approvals.

## Required relationships

Executive strategy must feed:

* Planning and Performance.
* Departmental workplans.
* Product strategy.
* Marketing priorities.
* Commercial targets.
* Financial budgets.
* Project priorities.
* Company Intelligence Engine.

## Example flow

```text
Executive Defines Strategic Objective
→ Objective Is Approved
→ Departments Receive Relevant Objectives
→ Planning Creates Measurable Targets
→ Workplans Are Created
→ Actual Results Are Collected
→ Intelligence Engine Evaluates Progress
→ Executive Reviews Performance
→ Corrective Decision Is Issued
```

---

# 7. MODULE TWO: HR AND ORGANIZATION

## Purpose

Define who works for the company, where they belong, what they are responsible for and who supervises them.

## Required capabilities

* Departments.
* Business units.
* Positions.
* Job descriptions.
* Employees.
* Employment information.
* Reporting lines.
* Supervisors.
* Teams.
* Skills and competencies.
* Staff documents.
* Leave management.
* Attendance where required.
* Employee lifecycle.
* Onboarding.
* Offboarding.
* Disciplinary records where authorized.
* Performance review history.
* Training and development.
* Staff assignment to projects, products and territories.
* Payroll readiness information.
* Emergency contacts where legally appropriate.

## Critical rule

Users and employees are related but not identical.

A system user account provides access.

An employee profile represents a member of the organization.

An employee may temporarily have no active user account.

A user account should, where appropriate, be linked to an employee record.

## Required integrations

HR sends accountability information to:

* Planning and Performance.
* Projects.
* Commercial Operations.
* Engineering.
* Finance.
* Approvals.
* Reports.
* Company Intelligence Engine.

---

# 8. MODULE THREE: PLANNING AND PERFORMANCE

## Purpose

Translate company strategy into measurable annual, quarterly, monthly, weekly and individual execution.

## Required capabilities

* Planning years.
* Strategic pillars.
* Strategic objectives.
* Annual company targets.
* Department targets.
* Employee targets.
* Quarterly allocations.
* Monthly allocations.
* Weekly commitments.
* Daily tasks.
* Key performance indicators.
* KPI formulas.
* Target units.
* Baselines.
* Target owners.
* Supervisors.
* Expected progress.
* Actual progress.
* Evidence submission.
* Evidence verification.
* Target approval.
* Workplan approval.
* Change requests.
* Blockers.
* Recovery plans.
* Corrective actions.
* Escalations.
* Performance reviews.
* Company scorecard.
* Department scorecards.
* Employee scorecards.
* Performance history.

## Performance status examples

* Not started.
* Ahead.
* On track.
* At risk.
* Behind.
* Blocked.
* Awaiting evidence.
* Awaiting verification.
* Completed.
* Cancelled.

## Critical rule

Official achievement must not depend exclusively on manually entered percentages.

Where possible, actual achievement should be calculated from verified records in connected modules.

---

# 9. MODULE FOUR: MARKETING AND COMMUNICATIONS

## Purpose

Create market awareness, manage the company brand, generate demand and produce qualified leads.

## Required capabilities

* Marketing plans.
* Campaigns.
* Campaign objectives.
* Target audiences.
* Channels.
* Campaign budgets.
* Content calendar.
* Content items.
* Social media activities.
* Advertising activities.
* Events.
* Partnerships.
* Brand assets.
* Communication materials.
* Publication approvals.
* Campaign leads.
* Campaign attribution.
* Campaign expenses.
* Engagement metrics.
* Cost per lead.
* Conversion rate.
* Campaign-generated opportunities.
* Campaign-generated revenue.
* Marketing reports.

## Required flow

```text
Marketing Plan
→ Campaign
→ Content or Activity
→ Audience Engagement
→ Lead Generated
→ Lead Sent to Commercial Operations
→ Commercial Conversion Outcome Returned to Marketing
→ Campaign ROI Calculated
```

---

# 10. MODULE FIVE: COMMERCIAL OPERATIONS

## Purpose

Manage the complete revenue execution lifecycle from prospect identification to commercial agreement, billing request, project handover, renewal and account expansion.

Commercial Operations is the revenue execution engine of TEMS.

Do not unnecessarily separate Sales from Commercial Operations.

Sales activities should be implemented as part of Commercial Operations unless the existing architecture has a valid reason for preserving a separate Sales workspace.

## Required capabilities

* Lead management.
* Prospect organizations.
* Stakeholder management.
* Lead qualification.
* Commercial activities.
* Calls.
* Emails.
* Meetings.
* Site visits.
* Demonstrations.
* Discovery assessments.
* Customer requirements.
* Opportunity management.
* Opportunity stages.
* Opportunity value.
* Opportunity probability.
* Expected closing date.
* Competitor information.
* Decision-process mapping.
* Budget confirmation.
* Commercial risk assessment.
* Solution design.
* Proposal management.
* Quotation management.
* Pricing.
* Discounts.
* Discount approvals.
* Negotiation history.
* Customer acceptance.
* Contract preparation.
* Contract approval.
* Contract signature.
* Commercial handover.
* Billing request.
* Renewal.
* Upselling.
* Cross-selling.
* Expansion opportunity.
* Lost opportunity analysis.

## Standard commercial lifecycle

```text
Market Opportunity
→ Lead
→ Qualification
→ Stakeholder Engagement
→ Discovery
→ Meeting
→ Site Visit
→ Demonstration
→ Requirements
→ Opportunity
→ Solution Design
→ Proposal
→ Quotation
→ Negotiation
→ Commercial Approval
→ Contract
→ Billing Request
→ Finance Invoice
→ Payment
→ Project Handover
→ Customer Success
→ Renewal
→ Expansion
```

## Mandatory stage controls

Each opportunity stage should define:

* Required information.
* Required documents.
* Required approvals.
* Responsible owner.
* Entry criteria.
* Exit criteria.
* Allowed next stages.
* Probability.
* Expected output.
* Notifications.
* Audit events.

## Commercial handover requirements

An opportunity should not be handed to implementation without:

* Confirmed customer organization.
* Confirmed stakeholders.
* Approved scope.
* Approved quotation.
* Signed contract or accepted commercial authorization.
* Payment terms.
* Delivery commitments.
* Implementation requirements.
* Responsible account owner.
* Internal approval.

---

# 11. MODULE SIX: CRM AND CUSTOMER ACCOUNTS

## Purpose

Maintain a complete enterprise view of every organization, customer and stakeholder.

## Required capabilities

* Organizations.
* Organization categories.
* Prospects.
* Customers.
* Partners.
* Suppliers where appropriate.
* Government organizations.
* NGOs.
* Schools.
* Healthcare facilities.
* SMEs.
* Stakeholders.
* Decision makers.
* Influencers.
* Technical contacts.
* Finance contacts.
* Contact details.
* Branches.
* Locations.
* Communication history.
* Engagement timeline.
* Customer products.
* Customer contracts.
* Customer projects.
* Customer invoices.
* Customer payments.
* Support history.
* Customer health.
* Customer risk.
* Customer documents.
* Relationship ownership.
* Account plans.

## Critical rule

The organization record should be the common enterprise identity used across:

* Commercial Operations.
* Finance.
* Projects.
* Customer Success.
* Legal.
* Reporting.
* Intelligence.

Do not maintain separate unrelated customer tables in multiple modules.

---

# 12. MODULE SEVEN: PRODUCT MANAGEMENT

## Purpose

Manage the products and services that Texaro designs, develops, markets, sells and supports.

## Required capabilities

* Product portfolio.
* Product categories.
* Products.
* Services.
* Product owners.
* Product lifecycle stages.
* Product versions.
* Product modules.
* Product features.
* Product roadmap.
* Product requirements.
* Product pricing.
* Pricing models.
* Subscription plans.
* Licensing models.
* Product costs.
* Product revenue.
* Product profitability.
* Product documentation.
* Release plans.
* Customer feature requests.
* Product feedback.
* Product risks.
* Product dependencies.
* Product health score.
* Product retirement.

## Suggested lifecycle stages

* Idea.
* Research.
* Validation.
* Approved.
* In development.
* Pilot.
* Active.
* Growth.
* Mature.
* Maintenance.
* Deprecated.
* Retired.

## Required integrations

Product Management must connect with:

* Marketing.
* Commercial Operations.
* Engineering.
* Projects.
* Finance.
* Customer Success.
* Intelligence.

---

# 13. MODULE EIGHT: ENGINEERING AND SOFTWARE DEVELOPMENT

## Purpose

Manage the technical work required to build, improve, test, release and maintain Texaro software products.

## Required capabilities

* Engineering projects.
* Product linkage.
* Requirements.
* Backlog.
* Epics.
* User stories.
* Technical tasks.
* Bugs.
* Issues.
* Priorities.
* Estimates.
* Assignments.
* Sprints where used.
* Development environments.
* Testing environments.
* Production environments.
* Code-review records.
* Quality assurance.
* Test cases.
* Test results.
* Release candidates.
* Releases.
* Deployment records.
* Rollback records.
* Technical risks.
* Security issues.
* Technical debt.
* System documentation.
* Architecture decisions.
* Change logs.
* Incident linkage.
* Customer-request linkage.

## Critical distinction

Engineering manages software construction.

Projects and Implementation manages delivery to a customer.

A customer implementation may depend on Engineering, but it is not the same as an Engineering project.

---

# 14. MODULE NINE: PROJECTS AND IMPLEMENTATION

## Purpose

Manage internal and customer projects from initiation to closure.

## Required capabilities

* Project requests.
* Project approval.
* Project charter.
* Project customer.
* Project owner.
* Project manager.
* Project team.
* Project scope.
* Requirements.
* Deliverables.
* Milestones.
* Tasks.
* Dependencies.
* Risks.
* Issues.
* Decisions.
* Change requests.
* Budget.
* Costs.
* Time tracking where required.
* Progress reporting.
* Customer approvals.
* Test and acceptance records.
* Training.
* Deployment.
* Go-live.
* Handover.
* Project closure.
* Post-implementation review.

## Commercial-to-project flow

```text
Contract Signed
→ Billing Terms Confirmed
→ Required Payment Confirmed
→ Commercial Handover Created
→ Project Request Created
→ Project Manager Assigned
→ Kickoff Conducted
→ Requirements Confirmed
→ Implementation Executed
→ Testing Completed
→ Training Completed
→ Customer Acceptance Obtained
→ Go-Live Completed
→ Customer Success Handover
```

---

# 15. MODULE TEN: CUSTOMER SUCCESS AND SUPPORT

## Purpose

Ensure that customers successfully adopt Texaro products, receive support, renew their agreements and expand their relationship with the company.

## Required capabilities

* Customer onboarding.
* Onboarding checklist.
* Customer training.
* User adoption.
* Product usage.
* Account reviews.
* Support tickets.
* Ticket categories.
* Ticket priorities.
* Ticket assignment.
* SLA tracking.
* Escalations.
* Complaints.
* Incidents.
* Service requests.
* Maintenance visits.
* Customer satisfaction.
* Surveys.
* Customer health score.
* Churn risk.
* Renewal readiness.
* Subscription expiry.
* Licence expiry.
* Renewal opportunities.
* Upselling.
* Cross-selling.
* Customer success plans.
* Customer communication history.

## Customer health factors

Customer health may consider:

* Product usage.
* Support-ticket volume.
* Outstanding invoices.
* Contract status.
* SLA performance.
* Customer satisfaction.
* Training completion.
* Engagement frequency.
* Unresolved complaints.
* Renewal proximity.

---

# 16. MODULE ELEVEN: FINANCE AND ADMINISTRATION

## Purpose

Control company money, budgets, accounting records, revenue, expenses, receivables, payables and financial reporting.

## Required capabilities

* Financial years.
* Financial periods.
* Chart of accounts.
* General ledger.
* Journals.
* Journal entries.
* Bank accounts.
* Mobile-money accounts.
* Cash accounts.
* Bank reconciliation.
* Customer invoices.
* Credit notes.
* Receipts.
* Payment allocation.
* Accounts receivable.
* Accounts payable.
* Supplier bills.
* Supplier payments.
* Expenses.
* Expense requests.
* Expense approvals.
* Budgeting.
* Budget lines.
* Budget commitments.
* Budget utilization.
* Budget variance.
* Cash-flow planning.
* Revenue records.
* Revenue recognition.
* Deferred revenue where applicable.
* Taxes.
* Withholding tax.
* VAT configuration where applicable.
* Fixed assets.
* Payroll posting readiness.
* Product profitability.
* Project profitability.
* Customer profitability.
* Financial statements.
* Income statement.
* Balance sheet.
* Cash-flow statement.
* Audit trail.

## Ownership rule

Commercial Operations may prepare quotations and initiate billing requests.

Finance must own:

* Official accounting invoice posting.
* Receipts.
* Payment allocation.
* Receivables.
* Credit notes.
* Tax treatment.
* Accounting entries.
* Financial-period control.
* Revenue recognition.

## Billing flow

```text
Approved Contract or Billing Milestone
→ Billing Request
→ Finance Review
→ Invoice Created
→ Customer Notified
→ Receivable Created
→ Payment Received
→ Receipt Created
→ Payment Allocated
→ Commercial and Project Status Updated
```

---

# 17. MODULE TWELVE: PROCUREMENT, ASSETS AND INVENTORY

## Purpose

Manage organizational purchasing, suppliers, equipment, stock and operational assets.

## Required capabilities

* Purchase requests.
* Purchase approvals.
* Requests for quotation.
* Supplier quotations.
* Purchase orders.
* Goods received.
* Supplier invoices.
* Supplier payments.
* Suppliers.
* Supplier performance.
* Products and stock items.
* Stock locations.
* Stock receipts.
* Stock issues.
* Stock transfers.
* Stock adjustments.
* Reorder levels.
* Asset register.
* Asset categories.
* Asset assignment.
* Asset custody.
* Asset maintenance.
* Asset depreciation information where required.
* Asset disposal.
* Customer deployment equipment.

## Strategic focus

TEMS is primarily managing a technology company.

Inventory should support technology operations and should not dominate the system.

Priority items may include:

* Computers.
* Printers.
* Routers.
* Networking equipment.
* Customer deployment hardware.
* Office equipment.
* Accessories.
* Promotional materials.
* Consumables.

---

# 18. MODULE THIRTEEN: LEGAL AND COMPLIANCE

## Purpose

Manage legal obligations, contracts, licences, policies, regulatory records and compliance risks.

## Required capabilities

* Legal contract review.
* Contract templates.
* Corporate registrations.
* Licences.
* Permits.
* Regulatory obligations.
* Compliance calendar.
* Data-protection compliance.
* Telecommunications compliance.
* KYC documentation.
* Intellectual property.
* Trademarks.
* Software licences.
* Policies.
* Policy approvals.
* Policy acknowledgements.
* Legal correspondence.
* Legal matters.
* Legal risks.
* Regulatory submissions.
* Compliance evidence.
* Expiry reminders.
* Document retention requirements.

## Relevant use cases

The module should support records such as:

* Africa's Talking KYC.
* Sender ID approvals.
* Customer contracts.
* Software licences.
* Data-processing agreements.
* Service-level agreements.
* Company incorporation documents.
* Tax registrations.
* Regulatory correspondence.

---

# 19. MODULE FOURTEEN: BOARD AND GOVERNANCE

## Purpose

Support the formal governance activities of Texaro Technologies Limited.

## Required capabilities

* Board members.
* Board roles.
* Committees.
* Board calendar.
* Board meetings.
* Agendas.
* Board packs.
* Papers.
* Resolutions.
* Decisions.
* Voting where required.
* Action items.
* Reserved matters.
* Director declarations.
* Conflict-of-interest records.
* Governance documents.
* Board performance.
* Committee reports.
* Approval records.

Access to Board information must be highly restricted.

---

# 20. MODULE FIFTEEN: KNOWLEDGE AND DOCUMENT MANAGEMENT

## Purpose

Preserve organizational knowledge and provide controlled access to official documents.

## Required capabilities

* Documents.
* Document categories.
* Document folders.
* Document owners.
* Versions.
* Reviews.
* Approvals.
* Effective dates.
* Expiry dates.
* Confidentiality classification.
* Access permissions.
* Templates.
* Policies.
* Procedures.
* Manuals.
* Contracts.
* Project documents.
* Product documents.
* Technical documents.
* Meeting minutes.
* Search.
* Tags.
* Document relationships.
* Retention rules.
* Download and access logs where required.

Avoid storing the same document separately in many modules.

Instead, maintain a central document record that may be linked to relevant business entities.

---

# 21. MODULE SIXTEEN: COMPANY INTELLIGENCE ENGINE

## Purpose

Convert enterprise data into explanations, forecasts, risks, opportunities and management recommendations.

The Intelligence Engine must go beyond ordinary reporting.

Reports explain what happened.

The Intelligence Engine should help explain:

* What is happening?
* Why is it happening?
* What is likely to happen next?
* What risks are emerging?
* What opportunities are emerging?
* What should management do?

## Required capabilities

* Company Health Score.
* Customer Health Score.
* Product Health Score.
* Workforce Health Score.
* Project Health Score.
* Commercial Pipeline Health.
* Financial Sustainability Score.
* Revenue forecasting.
* Cash-flow forecasting.
* Renewal forecasting.
* Churn-risk detection.
* Performance-risk detection.
* Project-delay prediction.
* Opportunity detection.
* Anomaly detection.
* Trend analysis.
* Scenario planning.
* Executive recommendations.
* Department recommendations.
* Daily or weekly executive briefs.
* Intelligence explanations.
* Confidence indicators.
* Recommendation tracking.

## Implementation approach

Begin with rules and transparent formulas.

Do not immediately depend on advanced machine learning.

Phase the engine as follows:

1. Descriptive intelligence.
2. Diagnostic intelligence.
3. Predictive intelligence.
4. Prescriptive intelligence.
5. AI-assisted executive decision support.

All automated recommendations must identify their source data and reasoning factors.

---

# 22. MODULE SEVENTEEN: REPORTS AND ANALYTICS

## Purpose

Provide operational and management reports across all modules.

## Required report categories

* Executive reports.
* Strategy reports.
* Workplan reports.
* Employee performance.
* Department performance.
* Marketing performance.
* Commercial pipeline.
* Conversion reports.
* Revenue reports.
* Receivables.
* Payables.
* Cash flow.
* Budget variance.
* Project status.
* Engineering progress.
* Product performance.
* Customer support.
* Customer health.
* Renewal reports.
* Legal compliance.
* Procurement.
* Assets.
* Board reports.
* Audit reports.

Reports should support, where practical:

* Date filters.
* Department filters.
* Employee filters.
* Product filters.
* Customer filters.
* Status filters.
* Export to PDF.
* Export to Excel or CSV.
* Print-friendly views.
* Drill-down to source records.

---

# 23. MODULE EIGHTEEN: USERS, ROLES AND SECURITY

## Purpose

Control system access and protect enterprise information.

## Required capabilities

* Users.
* Roles.
* Permissions.
* Role-permission assignment.
* User-role assignment.
* Module access.
* Record-level access where needed.
* Approval authority.
* Segregation of duties.
* Account status.
* Password control.
* Two-factor authentication readiness.
* Session management.
* Login history.
* Failed-login records.
* Device or IP visibility where legally appropriate.
* Audit logs.
* Sensitive-action confirmation.
* User impersonation only for authorized technical support with full audit logs.
* Permission review.
* Account deactivation.
* Employee offboarding integration.

## Security expectations

Implement:

* Server-side authorization.
* Policies or gates.
* Input validation.
* CSRF protection.
* Secure file access.
* Rate limiting.
* Protection from mass-assignment vulnerabilities.
* Database transactions.
* Secure password storage.
* Sensitive-data redaction.
* Audit trails.
* Controlled deletion.
* Soft deletion where appropriate.
* Data export controls.

Never rely only on hiding buttons in the user interface.

---

# 24. MODULE NINETEEN: SYSTEM CONFIGURATION

## Purpose

Provide controlled configuration of company-wide behavior.

## Required settings

* Company information.
* Company logo.
* Branding.
* Contact information.
* Financial year.
* Currency.
* Time zone.
* Date formats.
* Number formats.
* Tax settings.
* Document numbering.
* Quotation numbering.
* Contract numbering.
* Invoice numbering.
* Receipt numbering.
* Project numbering.
* Approval thresholds.
* Discount limits.
* Communication settings.
* Email settings.
* SMS settings.
* Notification settings.
* Security settings.
* Password policies.
* File limits.
* Supported file types.
* Default statuses.
* Feature flags.
* Module activation.
* System maintenance mode.
* Data-retention settings.

Configuration changes must be audited.

---

# 25. ENTERPRISE FLOW ONE: STRATEGY TO PERFORMANCE

Implement the following connected flow:

```text
Executive Defines Strategy
→ Strategic Objective Created
→ Objective Approved
→ Planning Year Created
→ Department Targets Created
→ Employee Targets Assigned
→ Weekly Commitments Created
→ Work Executed
→ Evidence Captured
→ Evidence Verified
→ Actual Performance Calculated
→ Department Score Updated
→ Company Score Updated
→ Intelligence Engine Detects Risks
→ Executive Reviews Recommendations
→ Corrective Action Created
```

---

# 26. ENTERPRISE FLOW TWO: MARKETING TO REVENUE

```text
Marketing Campaign Created
→ Campaign Activity Executed
→ Lead Generated
→ Lead Attribution Recorded
→ Lead Sent to Commercial Operations
→ Lead Qualified
→ Organization and Stakeholders Confirmed
→ Discovery Conducted
→ Opportunity Created
→ Proposal Prepared
→ Quotation Approved
→ Negotiation Completed
→ Contract Signed
→ Billing Request Submitted
→ Finance Creates Invoice
→ Payment Received
→ Revenue Recorded
→ Marketing Receives Conversion Result
→ Campaign ROI Updated
```

---

# 27. ENTERPRISE FLOW THREE: OPPORTUNITY TO CUSTOMER SUCCESS

```text
Qualified Opportunity
→ Requirements Captured
→ Solution Designed
→ Proposal Approved
→ Quotation Accepted
→ Contract Signed
→ Payment Conditions Met
→ Project Request Created
→ Implementation Project Approved
→ Customer Kickoff Conducted
→ Requirements Confirmed
→ System Configured or Developed
→ Testing Completed
→ Customer Training Completed
→ Acceptance Signed
→ Go-Live Completed
→ Customer Success Account Activated
→ Support and Adoption Monitored
→ Renewal Prepared
→ Renewal Completed
→ Expansion Opportunity Created
```

---

# 28. ENTERPRISE FLOW FOUR: PRODUCT IDEA TO MARKET

```text
Product Idea Submitted
→ Idea Reviewed
→ Market Need Assessed
→ Business Case Prepared
→ Product Approved
→ Product Roadmap Created
→ Engineering Requirements Created
→ Development Work Executed
→ Quality Assurance Completed
→ Release Approved
→ Product Published to Commercial Catalog
→ Marketing Campaign Created
→ Commercial Team Enabled
→ Customers Acquired
→ Customer Feedback Captured
→ Product Roadmap Updated
```

---

# 29. ENTERPRISE FLOW FIVE: PROCUREMENT TO PAYMENT

```text
Purchase Need Identified
→ Purchase Request Created
→ Budget Availability Checked
→ Request Approved
→ Supplier Quotations Collected
→ Supplier Selected
→ Purchase Order Created
→ Goods or Services Received
→ Receipt Verified
→ Supplier Invoice Recorded
→ Payment Approval Requested
→ Finance Pays Supplier
→ Payment Recorded
→ Asset or Inventory Updated
→ Budget Utilization Updated
```

---

# 30. ENTERPRISE FLOW SIX: SUPPORT TO PRODUCT IMPROVEMENT

```text
Customer Raises Ticket
→ Ticket Classified
→ Ticket Assigned
→ SLA Timer Starts
→ Support Investigates
→ Issue Resolved or Escalated
→ Engineering Issue Created Where Required
→ Product Fix Developed
→ Fix Tested
→ Release Deployed
→ Customer Notified
→ Ticket Closed
→ Customer Satisfaction Captured
→ Product Health Updated
```

---

# 31. ENTERPRISE DATA ARCHITECTURE

Review the existing database before creating new tables.

Reuse appropriate entities and remove unnecessary duplication through controlled migrations.

The major enterprise entities should include, where appropriate:

## Organization entities

* Company.
* Department.
* Business unit.
* Position.
* Employee.
* Team.
* User.
* Role.
* Permission.

## Strategy and planning entities

* Planning year.
* Strategic pillar.
* Strategic objective.
* Initiative.
* Target.
* KPI.
* Workplan.
* Commitment.
* Task.
* Evidence.
* Evidence review.
* Performance review.
* Corrective action.
* Risk.

## Market and commercial entities

* Campaign.
* Lead.
* Organization.
* Stakeholder.
* Commercial activity.
* Meeting.
* Site visit.
* Discovery.
* Requirement.
* Opportunity.
* Opportunity stage history.
* Proposal.
* Proposal version.
* Quotation.
* Quotation item.
* Negotiation.
* Contract.
* Billing request.
* Renewal.
* Commercial handover.

## Product and engineering entities

* Product.
* Product module.
* Product feature.
* Product version.
* Product roadmap item.
* Product requirement.
* Engineering project.
* Epic.
* User story.
* Bug.
* Technical task.
* Sprint.
* Test case.
* Test result.
* Release.
* Deployment.
* Architecture decision.

## Project entities

* Project.
* Project member.
* Project milestone.
* Project task.
* Project dependency.
* Project risk.
* Project issue.
* Project decision.
* Change request.
* Acceptance.
* Training.
* Deployment.
* Project handover.
* Project closure.

## Customer-success entities

* Customer account.
* Onboarding plan.
* Support ticket.
* SLA.
* Customer training.
* Customer health assessment.
* Survey.
* Complaint.
* Incident.
* Maintenance activity.
* Renewal readiness.
* Customer success plan.

## Financial entities

* Financial year.
* Financial period.
* Account.
* Journal.
* Journal entry.
* Journal entry line.
* Budget.
* Budget line.
* Invoice.
* Invoice item.
* Receipt.
* Payment.
* Payment allocation.
* Credit note.
* Expense.
* Supplier bill.
* Bank account.
* Bank reconciliation.
* Asset.
* Tax record.

## Procurement entities

* Supplier.
* Purchase request.
* Request for quotation.
* Supplier quotation.
* Purchase order.
* Goods receipt.
* Stock item.
* Stock location.
* Stock movement.
* Asset assignment.
* Asset maintenance.

## Governance entities

* Legal document.
* Compliance obligation.
* Licence.
* Policy.
* Board member.
* Board meeting.
* Board paper.
* Resolution.
* Governance action.
* Audit log.

## Knowledge entities

* Document.
* Document version.
* Document category.
* Document link.
* Document approval.
* Knowledge article.

## Intelligence entities

* Metric definition.
* Metric snapshot.
* Health score.
* Forecast.
* Risk signal.
* Opportunity signal.
* Recommendation.
* Recommendation action.
* Executive brief.

Use clear foreign keys and avoid polymorphic relationships where they would reduce integrity or make reporting unnecessarily difficult.

Use polymorphic relationships only when their benefits are clear.

---

# 32. DOMAIN EVENT ARCHITECTURE

Use domain events or equivalent application events for important cross-module transitions.

Possible events include:

* StrategicObjectiveApproved.
* WorkplanApproved.
* TargetAssigned.
* EvidenceSubmitted.
* EvidenceVerified.
* LeadCreated.
* LeadQualified.
* OpportunityCreated.
* OpportunityStageChanged.
* ProposalApproved.
* QuotationApproved.
* QuotationAccepted.
* ContractSigned.
* BillingRequested.
* InvoiceIssued.
* PaymentReceived.
* PaymentAllocated.
* ProjectCreated.
* ProjectMilestoneCompleted.
* ProjectGoLiveCompleted.
* CustomerOnboardingCompleted.
* SupportTicketEscalated.
* ProductReleasePublished.
* RenewalDue.
* RenewalCompleted.
* ComplianceDeadlineApproaching.
* BudgetThresholdExceeded.
* PerformanceRiskDetected.

Events should trigger appropriate actions such as:

* Notifications.
* Task creation.
* Performance evidence.
* Status updates.
* Audit records.
* Financial records.
* Project creation.
* Dashboard refresh.
* Intelligence recalculation.

Do not place large amounts of unrelated business logic directly inside controllers.

Use appropriate services, actions, jobs, listeners or domain classes.

---

# 33. APPROVAL ARCHITECTURE

Develop a reusable approval framework rather than implementing completely separate approval logic for every module.

Approval requests may apply to:

* Strategy.
* Workplans.
* Targets.
* Budgets.
* Expenses.
* Purchase requests.
* Quotations.
* Discounts.
* Contracts.
* Billing requests.
* Payments.
* Projects.
* Change requests.
* Product releases.
* Policies.
* Legal documents.

The approval framework should support:

* Request type.
* Source record.
* Requester.
* Current approver.
* Approval steps.
* Approval sequence.
* Approval threshold.
* Approve.
* Reject.
* Return for correction.
* Comments.
* Supporting documents.
* Escalation.
* Delegation.
* Decision date.
* Audit history.

Approval routing may depend on:

* Department.
* Role.
* Amount.
* Risk.
* Document type.
* Project.
* Product.
* Customer.
* Company policy.

---

# 34. NOTIFICATION ARCHITECTURE

Provide a centralized notification system supporting:

* In-app notifications.
* Email notifications.
* SMS notifications where configured.
* Scheduled reminders.
* Escalations.

Notification examples:

* Task assigned.
* Approval required.
* Approval completed.
* Target at risk.
* Evidence rejected.
* Meeting reminder.
* Opportunity idle.
* Quotation expiring.
* Contract expiring.
* Invoice overdue.
* Payment received.
* Project milestone due.
* Support SLA at risk.
* Subscription expiring.
* Compliance deadline approaching.
* Board action overdue.

Allow users to mark notifications as read and configure appropriate preferences.

Avoid sending duplicate notifications.

---

# 35. ROLE-BASED WORKSPACES

Each user role should receive a practical dashboard.

## Managing Director

Show:

* Company health.
* Revenue.
* Cash position.
* Commercial pipeline.
* Department performance.
* Project health.
* Product health.
* Customer risks.
* Strategic objectives.
* Major approvals.
* Executive actions.
* Critical alerts.
* Intelligence recommendations.

## Department Head

Show:

* Department workplan.
* Department targets.
* Staff performance.
* Current tasks.
* Approvals.
* Risks.
* Blockers.
* Budget utilization.
* Department reports.

## Commercial User

Show:

* Leads.
* Opportunities.
* Follow-ups.
* Meetings.
* Site visits.
* Quotations.
* Contracts.
* Pipeline target.
* Conversion rate.
* Expected revenue.
* Renewal opportunities.

## Finance User

Show:

* Receivables.
* Payables.
* Cash balances.
* Invoices.
* Payments.
* Expenses.
* Budget utilization.
* Reconciliations.
* Finance approvals.
* Evidence gaps.

## Project Manager

Show:

* Active projects.
* Milestones.
* Tasks.
* Risks.
* Issues.
* Customer approvals.
* Budget.
* Project health.
* Upcoming deadlines.

## Engineer

Show:

* Assigned stories.
* Bugs.
* Technical tasks.
* Releases.
* Test failures.
* Deployment status.
* Product priorities.

## Customer Success User

Show:

* Customer health.
* Open tickets.
* SLA risks.
* Renewals.
* Training.
* Onboarding.
* Complaints.
* At-risk customers.

## HR User

Show:

* Staff records.
* Leave.
* Positions.
* Reporting lines.
* Staff onboarding.
* Performance review status.
* Training requirements.

## Board User

Show only authorized:

* Board meetings.
* Board packs.
* Resolutions.
* Governance actions.
* High-level company reports.

---

# 36. USER INTERFACE REQUIREMENTS

Maintain a clean, professional enterprise design.

The interface should prioritize clarity over decoration.

## Required interface characteristics

* Responsive design.
* Desktop-first enterprise usability.
* Mobile support for approvals, tasks, meetings and field activity.
* Consistent navigation.
* Breadcrumbs.
* Search.
* Filters.
* Pagination.
* Saved filters where practical.
* Status badges.
* Clear action buttons.
* Confirmation before destructive actions.
* Empty states.
* Loading states.
* Error states.
* Permission-aware actions.
* Activity timelines.
* Detail pages with related records.
* Audit visibility where authorized.
* Export capability.
* Print-friendly documents.

## Navigation recommendation

Use a structured sidebar with grouped modules.

Suggested groups:

* Executive.
* People.
* Planning.
* Growth.
* Products.
* Delivery.
* Customers.
* Finance.
* Governance.
* Intelligence.
* Administration.

Do not overload the sidebar with every individual page.

Use module landing pages and internal navigation.

---

# 37. DOCUMENT GENERATION

TEMS should generate professional documents such as:

* Proposals.
* Quotations.
* Contracts.
* Invoices.
* Receipts.
* Purchase orders.
* Project charters.
* Requirement sign-offs.
* Acceptance certificates.
* Board papers.
* Performance reports.
* Financial reports.
* Customer statements.
* Licence certificates.

Documents should support:

* Company branding.
* Document numbers.
* Versions.
* Dates.
* Customer information.
* Approval information.
* Signatures where applicable.
* Terms and conditions.
* PDF export.
* Audit history.

---

# 38. TECHNICAL ARCHITECTURE EXPECTATIONS

Follow the existing framework and project conventions unless they are clearly harmful.

For a Laravel-based system, prefer:

* Thin controllers.
* Form-request validation.
* Policies and gates.
* Service or action classes for business operations.
* Events and listeners for cross-module effects.
* Queued jobs for slow processes.
* Notifications for user communication.
* Scheduled commands for reminders and health checks.
* Database transactions for multi-step business actions.
* Resource classes for APIs.
* Clear route organization.
* Reusable query scopes.
* Factories and seeders.
* Feature and unit tests.
* Consistent exception handling.
* Centralized audit logging.

Avoid:

* Large controllers.
* Duplicated business logic.
* Unvalidated mass assignment.
* Direct database queries scattered across views.
* Hard-coded roles.
* Hard-coded company information.
* Hard-coded financial rules.
* Business logic inside templates.
* Destructive migration shortcuts.
* Untracked status changes.

---

# 39. DEVELOPMENT SAFETY RULES

While changing the existing system:

1. Do not delete existing tables without first proving they are obsolete.
2. Do not drop production data.
3. Use additive migrations where possible.
4. Create backups before destructive migrations.
5. Preserve current routes or provide controlled redirects.
6. Preserve current user access unless security requires correction.
7. Preserve existing data through migration scripts.
8. Avoid renaming important fields without data migration.
9. Use feature flags for incomplete major modules.
10. Document important architectural decisions.
11. Keep the application deployable after each implementation phase.
12. Run tests after every major change.
13. Do not expose unfinished pages to unauthorized users.
14. Do not create duplicate models for entities that already exist.
15. Do not generate fake production financial records.
16. Seed only development or demonstration data safely.

---

# 40. TESTING REQUIREMENTS

Implement tests for critical flows.

At minimum, test:

* Authentication.
* Authorization.
* Role permissions.
* Strategy approval.
* Workplan approval.
* Target ownership.
* Evidence verification.
* Lead qualification.
* Opportunity stage transition.
* Quotation approval.
* Contract signing.
* Billing request.
* Invoice creation.
* Payment allocation.
* Project creation from commercial handover.
* Project milestone completion.
* Customer-success handover.
* Support SLA escalation.
* Budget availability.
* Purchase approval.
* Audit logging.
* Notification generation.
* Unauthorized-access rejection.

Tests must verify business rules, not only whether pages return HTTP 200.

---

# 41. AUDIT REQUIREMENTS

Audit the following where appropriate:

* Record creation.
* Important updates.
* Status transitions.
* Approvals.
* Rejections.
* Financial changes.
* Permission changes.
* User changes.
* Employee changes.
* Contract changes.
* Document downloads for highly confidential records.
* Deletions.
* Restorations.
* System configuration changes.
* Login activity.
* Data exports.

Audit records should include:

* User.
* Action.
* Entity type.
* Entity identifier.
* Previous values where appropriate.
* New values where appropriate.
* Date and time.
* IP or device information where appropriate.
* Reason or comment.
* Source module.

Audit records should not be casually editable.

---

# 42. IMPLEMENTATION PHASES

Follow a phased implementation.

## Phase 0: Discovery and stabilization

* Inspect the entire repository.
* Map existing functionality.
* Fix critical errors.
* Confirm authentication.
* Confirm permissions.
* Confirm migrations.
* Confirm database integrity.
* Document existing modules.
* Identify duplicate entities.
* Prepare gap analysis.

## Phase 1: Enterprise foundation

Implement or strengthen:

* Company configuration.
* Users.
* Roles.
* Permissions.
* Departments.
* Positions.
* Employees.
* Organization structure.
* Audit logs.
* Notifications.
* Document management foundation.
* Reusable approval engine.

## Phase 2: Strategy, planning and performance

Implement or strengthen:

* Executive strategy.
* Planning years.
* Objectives.
* Targets.
* Workplans.
* Weekly commitments.
* Evidence.
* Verification.
* Scorecards.
* Corrective actions.

## Phase 3: Growth and revenue

Implement or strengthen:

* Marketing.
* CRM.
* Commercial Operations.
* Leads.
* Organizations.
* Stakeholders.
* Opportunities.
* Proposals.
* Quotations.
* Contracts.
* Billing requests.
* Renewals.

## Phase 4: Finance and procurement

Implement or strengthen:

* Chart of accounts.
* Budgets.
* Invoices.
* Receipts.
* Payments.
* Receivables.
* Payables.
* Expenses.
* Procurement.
* Assets.
* Inventory.
* Financial reporting.

## Phase 5: Products and delivery

Implement:

* Product Management.
* Engineering.
* Projects.
* Implementation.
* Releases.
* Deployments.
* Customer handover.

## Phase 6: Customer success and governance

Implement:

* Customer onboarding.
* Support.
* SLA tracking.
* Customer health.
* Renewals.
* Legal.
* Compliance.
* Board management.

## Phase 7: Intelligence

Implement:

* Enterprise KPIs.
* Health scores.
* Forecasting.
* Risk detection.
* Recommendations.
* Executive intelligence dashboard.
* Board intelligence dashboard.

---

# 43. REQUIRED FIRST RESPONSE FROM CODEX

Before implementing major changes, respond with:

## A. Existing system assessment

Include:

* Framework and version.
* Main architecture.
* Existing modules.
* Existing models.
* Existing tables.
* Existing permissions.
* Existing workflows.
* Existing dashboards.
* Existing integrations.
* Existing tests.

## B. Gap analysis

For every required TEMS module, classify it as:

* Complete.
* Partially complete.
* Missing.
* Present but structurally incorrect.
* Present but not integrated.

## C. Critical issues

Identify:

* Data duplication.
* Broken relationships.
* Security gaps.
* Missing authorization.
* Missing audit trails.
* Weak financial controls.
* Workflow gaps.
* Architectural inconsistencies.
* Performance risks.
* Migration risks.

## D. Implementation proposal

Provide:

* Recommended module order.
* Migration strategy.
* Refactoring strategy.
* Data-preservation strategy.
* Testing strategy.
* Estimated implementation phases.
* Files expected to change in the first phase.

Do not provide a vague summary.

Ground the assessment in the actual repository.

---

# 44. CODING INSTRUCTION FORMAT

For every implementation task:

1. State the business problem.
2. Identify existing related code.
3. Explain the intended solution.
4. List files that will be created or modified.
5. Implement the smallest stable vertical slice.
6. Add or update migrations.
7. Add authorization.
8. Add validation.
9. Add tests.
10. Run relevant tests.
11. Report what was completed.
12. Report remaining work.
13. Mention any assumptions.
14. Mention any migration or deployment risks.

Do not claim completion unless the code, database, permissions, workflow and tests are all addressed.

---

# 45. DEFINITION OF DONE

A feature is only complete when:

* The database structure exists.
* Relationships are correct.
* Validation is implemented.
* Authorization is implemented.
* Business rules are enforced.
* User interface exists.
* Relevant notifications exist.
* Audit logging exists where required.
* Integration events are handled.
* Reports or dashboard impact is considered.
* Tests pass.
* Existing functionality remains operational.
* The feature is documented.
* No critical placeholders remain.

---

# 46. FINAL SYSTEM EXPECTATION

The completed TEMS system must allow Texaro Technologies Limited to understand and control:

* What the company is trying to achieve.
* Who is responsible.
* What every department is doing.
* What every employee is accountable for.
* Which products are being developed.
* Which markets are being targeted.
* Which leads are being pursued.
* Which customers are being served.
* Which contracts are active.
* Which invoices are unpaid.
* How much cash is available.
* Which projects are delayed.
* Which customers are at risk.
* Which products are profitable.
* Which departments are behind.
* Which legal obligations are approaching.
* Which decisions require executive action.
* Whether the company is progressing, stagnating or declining.
* What management should do next.

TEMS must not merely store company data.

It must guide company execution, protect accountability, coordinate departments, measure verified performance and support better decisions.

---

# 47. STARTING COMMAND

Begin by auditing the current TEMS repository.

Do not rebuild the application from zero.

Do not remove functioning features.

Map the current codebase against this master architecture.

Then produce the required existing-system assessment, gap analysis, critical-issues report and phased implementation proposal.

After the assessment, begin with the highest-priority foundational gaps while preserving the existing system and database.

The final goal is to transform the current application into the complete **Texaro Enterprise Management System Enterprise Operating System** described in this prompt.
