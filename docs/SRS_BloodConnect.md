# Software Requirements Specification (SRS) for BloodConnect

## 1. Introduction

### 1.1 Purpose
The purpose of this document is to define the software requirements for the BloodConnect system — a web-based blood donation management platform. It aims to connect blood donors with patients in need, manage the complete request-to-donation lifecycle, maintain a central blood bank inventory for hospitals, and certify donors after a completed donation.

### 1.2 Scope
The System will:
- Allow users to register as a Patient, Donor, or Hospital, log in, and manage their profiles.
- Enable Patients to create, track, cancel, reactivate, and download receipts for blood requests.
- Enable Donors to view matching blood requests, accept or decline them, record standalone donations, and view their donation history and certificates.
- Enable Hospitals to view central blood stock and submit blood stock requests.
- Enable Administrators to manage users, donors, hospitals, blood requests, hospital stock requests, central stock, roles, permissions, and generate reports.
- Track individual blood bags with expiry dates and allocate stock on a First-Expiry-First-Out (FEFO) basis.
- Send in-app notifications and email alerts for request assignments, donations, stock events, and system events.
- Generate donation certificates (PDF) for completed donations and export administrative reports to PDF and Excel.

### 1.3 Definitions
- **Admin / Administrator**: System administrator with full access (user_type_id = 1).
- **Donor**: Registered user who can donate blood (user_type_id = 2).
- **Patient**: Registered user who requests blood (user_type_id = 3).
- **Hospital**: Registered organization that requests blood bags from central stock (user_type_id = 4).
- **Blood Request**: A request created by a Patient specifying blood group, units, hospital, urgency, and location.
- **Standalone Donation**: A donation recorded by a Donor that is not linked to a specific patient request.
- **Central Stock**: The system-managed inventory of blood bags (blood_stock / blood_bags tables).
- **FEFO**: First-Expiry-First-Out — oldest-expiring available bags are allocated first.
- **Hospital Stock Request**: A request from a Hospital for a quantity of a blood group, tracked with allocated and pending quantities.
- **RBAC**: Role-Based Access Control — permissions assigned per user type.
- **Activity Log**: A record of system events for audit and dashboard display.

---

## 2. Overall Description

### 2.1 Product Perspective
Standalone web-based system built on a modular PHP architecture (Clean Architecture + DDD), deployed via XAMPP on a local or secure server. Accessible via standard web browsers. Uses MySQL for persistence, PDO for database access, Dompdf for PDF generation, a native XLSX generator for Excel exports, and Tailwind CSS for a responsive UI.

### 2.2 User Classes
| Class | Description |
|---|---|
| **Guest** | Unauthenticated visitor — can view public pages, register, or log in. |
| **Patient** | Can create and manage blood requests, search donors, view notifications, and download request receipts. |
| **Donor** | Can complete a donor profile, view matching requests, accept/decline, record standalone donations, and view history and certificates. |
| **Hospital** | Can view central stock, submit and track stock requests, and manage its profile. |
| **Administrator** | Full access to dashboard, user/donor/hospital management, blood requests, hospital requests, stock, roles, permissions, and reports. |

### 2.3 Assumptions
- Users have basic browser and internet knowledge.
- Email infrastructure exists for OTP and notification delivery.
- The deployment environment supports PHP 8+, MySQL, and Apache/Nginx.
- SSL certificate may be required for production deployment.
- Google reCAPTCHA v2 is optional and enabled only when site/secret keys are configured.

---

## 3. System Modules and Features

### Module: User Management

#### 1. User Registration/Login
This sub-module allows users to create accounts and securely access the system.
- **User Registration (Patient/Donor):**
  - Guests can sign up as a Patient or Donor by providing full name, email, phone, password, blood group, and address.
  - Passwords are securely stored using PHP `password_hash()` with the bcrypt algorithm.
  - Email uniqueness is enforced at both the database and application level.
  - Email verification via a one-time code (OTP) is required before the account is fully activated.
  - Google reCAPTCHA v2 (optional) prevents automated registrations when configured.
- **User Login:**
  - Users log in with their registered email and password; credentials are verified with `password_verify()`.
  - Session-based authentication stores `user_id`, `user_type_id`, and `username` in `$_SESSION`.
  - The session ID is regenerated on login; logout destroys the session.
  - On success, the user is redirected to their role dashboard (Patient → `/patient/dashboard`, Donor → `/donor/dashboard`, Hospital → `/hospital/dashboard`, Admin → `/admin/dashboard`).
- **Password Recovery:**
  - Users can request a password reset by entering their email; the system sends a one-time code (OTP).
  - The OTP is verified before the user is allowed to set a new password.
- **Email Verification:**
  - OTP-based email verification is provided during registration with a resend option.
- **Google OAuth 2.0:**
  - Users may sign in with Google; existing emails are auto-linked, and new users are prompted to choose a role and complete registration.

#### 2. Role Assignment (Admin, Donor, Patient, Hospital)
This part of the module controls what users can see and do based on their assigned role.
- **Role Definitions:**
  - **Admin**: Full access, including user, donor, hospital, request, stock, role, and report management.
  - **Donor**: Can complete a donor profile, view matching requests, accept/decline requests, record standalone donations, and view history/certificates.
  - **Patient**: Can create and manage blood requests, search donors, and manage personal settings.
  - **Hospital**: Can view central stock, submit and track stock requests, and manage the hospital profile.
- **Role Assignment:**
  - Roles are assigned during registration (Patient/Donor) or created by an Admin (Hospital accounts).
  - Role-based access control (RBAC) ensures users only access permitted features.
  - Permissions are stored in the `permissions` table and mapped to roles via `user_type_permissions`.

#### 3. Profile Management
Users have the ability to view and update their personal information.
- **Profile Viewing:**
  - Users can view their details such as name, email, phone, blood group, and address.
  - Donors can view additional profile data (weight, date of birth, state/region, township).
  - Hospitals can view their hospital name, license number, address, and contact phone.
- **Profile Editing:**
  - Users can update their contact information, blood group, address, and password.
  - Changes are validated (e.g., Myanmar phone format) and saved securely.
  - Admins can update other users' accounts or deactivate them.
- **Security Features:**
  - Password changes are bcrypt-hashed before storage.
  - Profile updates generate a "Profile Updated" notification to admins.
  - Activity log entries record profile changes for audit purposes.

---

### Module: Patient Blood Request Management

#### 1. Patient Dashboard
Landing page for authenticated Patients showing an overview of their activity.
- Displays the most recent blood requests (last 6).
- Shows request metrics (total, pending, accepted, cancelled) and unread notification count.
- Runs an overdue-request expiry check on every patient page load.

#### 2. Create Blood Request
Allows Patients to submit a new request for blood.
- **Form Fields**: Blood group needed, units required (1–2), hospital name, state/region, township, hospital address, urgency level (Low/Medium/High/Critical), and contact phone.
- **Business Rules**:
  - Only one request may be open (PENDING/ASSIGNED) at a time per patient.
  - A unique `request_code` (REQ + timestamp) is auto-generated.
  - Status defaults to PENDING (master id 7) with an urgency-based expiry date.
- **Side Effects**:
  - Admins are notified ("New Blood Request ... waiting for matching donors").
  - Matching donors (same blood group, location-prioritized) are notified.
  - An activity log entry `BLOOD_REQUEST_CREATED` is recorded.

#### 3. View My Requests
Lists all blood requests created by the logged-in Patient.
- Filterable by status: All, Pending, Accepted, Cancelled.
- Each row shows request code, blood group, hospital, urgency, status, and creation date.

#### 4. View Request Detail
Shows full details of a specific blood request.
- Includes patient info, hospital details, urgency, status, and expiry date.
- Displays assigned donor information (name, email, phone, blood group) when a donor is assigned.

#### 5. Cancel & Reactivate Request
Patients can manage the lifecycle of their requests.
- **Cancel**: A PENDING or ASSIGNED request can be cancelled; status becomes CANCELLED; admins and the assigned donor are notified.
- **Reactivate**: A CANCELLED request can be re-submitted; status returns to PENDING.

#### 6. Request Receipt (PDF)
Patients can download a PDF receipt for a request.
- Generates a Dompdf-based receipt containing request details.
- Streamed as an `application/pdf` attachment.

#### 7. Search Donors
Search page for Patients to find available donors.
- Search by blood group and location (township/state).
- Returns eligible donors matching the criteria.

---

### Module: Donor Management

#### 1. Complete Donor Profile
First-time Donors must complete their profile before using the system.
- **Fields**: Date of birth, weight (kg), state/region, township.
- **Eligibility Check**: DonorEligibilityService evaluates age (18–65) and weight (≥ 50 kg).
- **Availability**: `next_available_date` tracks the 3-month donation cooldown for frequency compliance.

#### 2. Donor Dashboard
Landing page showing matching requests and personal stats.
- Summary metrics: total donations, last donation date, and impact.
- Lists blood requests matching the donor's blood group and location.
- Shows recent activity and notifications.

#### 3. View Matching Blood Requests
Lists blood requests matching the Donor's blood group.
- Tiered matching: same township > same region/state > any region.
- Each request shows patient, hospital, urgency, blood group needed, and creation date.
- Badge count for pending requests.

#### 4. Accept Blood Request
Donor can accept a matching or assigned request.
- **Business Rules**:
  - The request must not be EXPIRED and must not be past its expiry date.
  - The donor profile must meet age/weight eligibility.
  - The donor must be within the donation cooldown window (`next_available_date`).
  - The donor cannot accept while another request is already ACCEPTED.
- **Process**:
  - Request status becomes ACCEPTED (8) with the donor recorded as assigned donor.
  - A `donation_history` record is created with status ACCEPTED.
  - The donor's `next_available_date` is set to +3 months.
- **Side Effects**:
  - Admins, donor, and patient receive in-app notifications.
  - Emails are sent to admins and the patient.

#### 5. Decline Blood Request
Donor can decline a request.
- The donor's response is recorded as DECLINED.
- The request remains available to other donors and may be reset to PENDING.

#### 6. Standalone Donation
Donors can record a donation that is not linked to a patient request.
- **Business Rules**:
  - A valid blood group must be set on the donor profile.
  - At most 2 bags per donation.
  - The donor must be eligible (3-month cooldown).
- **Process** (single transaction):
  - A `donation_history` row is created with status COMPLETED (9).
  - `recordBags()` adds the donated bags to central stock (AVAILABLE, expiry = collection date + 42 days).
  - The donor's `next_available_date` is set to +3 months.
- **Side Effects**:
  - Activity log `DONATION_RECORDED`.
  - Notifications to the donor and all admins.
  - Donation-completed email to the donor.

#### 7. Donation History
Lists all donations recorded for the Donor (request-linked and standalone).
- Shows donation date, blood group, bags, facility (hospital or Central Stock), and status.
- Detailed view of each donation record with a Certificate button when the status is COMPLETED.

#### 8. Donation Certificate
Once a donation is COMPLETED, the donor can view and download a certificate.
- **Web Preview**: `/donor/donation/certificate/view?id={donation_id}` shows the certificate with donor name, certificate number (BC- + 6-digit padded donation id), donation date, facility, and bags donated.
- **PDF Download**: `/donor/donation/certificate?id={donation_id}` streams a Dompdf-generated PDF (A4 landscape).
- **Access Control**: Routes are behind the donor middleware and `donation_history.view` permission; the lookup enforces donor ownership.

---

### Module: Hospital Blood Stock Management

#### 1. Hospital Dashboard
Landing page for authenticated Hospitals showing an overview of central stock and their requests.
- Enriched blood stock per group: available, reserved, expiring soon, expired, oldest expiry, last updated, and stock percentage.
- Alert feed: critical stock (0 bags), low stock (≤ 2 bags), pending request shortfalls, and stock-available-to-fulfill notices.
- Request summary: total requested/allocated/pending, open count, and recent requests.
- Recent stock adjustments and recent notifications.

#### 2. View Blood Stock
Read-only overview of central blood stock per blood group.
- Displays available bag counts and expiry information.

#### 3. Submit Stock Request
Hospitals can request blood bags from central stock.
- **Form Fields**: Blood group (one of 8 groups) and quantity (1–100).
- **Business Rules**:
  - Request is created in PENDING status; no stock is deducted at submission.
  - A unique `request_code` (HSR + timestamp + random) is auto-generated.
  - Tracks `quantity_requested`, `quantity_allocated`, and `quantity_pending`.
- **Side Effects**:
  - Notification to the hospital ("Stock Request Submitted").
  - Notification to all admins ("New Hospital Stock Request").
  - Activity log `STOCK_REQUEST_SUBMITTED`.

#### 4. View Requests & Pending Shortfalls
Hospitals can track the status of their stock requests.
- **My Requests**: Full list of own requests with status and allocation amounts.
- **Pending**: Open requests only (PENDING/PARTIAL with pending quantity > 0) plus the total pending quantity.

#### 5. Hospital Profile
Hospitals can view and update their profile.
- Fields: hospital name, license number, state/region, township, address, and phone.
- Updates are validated and saved securely.

---

### Module: Admin Management

#### 1. Admin Dashboard
Central overview page for administrators.
- **Summary Cards**: Total donors, patients, pending requests, and other key metrics.
- **Line Chart**: Blood requests over time (configurable period).
- **Doughnut Chart**: Donors by blood group.
- **Recent Blood Requests**: Latest requests with blood group, hospital, and date.
- **Recent System Activity**: Live feed of platform events with time-ago timestamps.
- **Chart Data**: JSON endpoint (`/admin/dashboard/chart-data`) feeds the charts.

#### 2. User Management
Full CRUD for all system users.
- **List View**: All users with search and filter by role/status.
- **View Detail**: Complete user profile including role, contact, and activity.
- **Edit User**: Update username, email, role, and active status.
- **Delete User**: Soft-delete (sets `deleted_at`).

#### 3. Donor Management
List and filter donors.
- Filter by availability/eligibility status.
- Shows donor eligibility, blood group, and location.
- Uses `syncAvailabilityStatus` (last donation date + `next_available_date`) for availability.

#### 4. Hospital Management
Manage hospital accounts.
- **List View**: All hospitals with profile details.
- **Create Hospital**: Admin creates a hospital account (user_type 4) plus a hospital profile row; the hospital is notified.

#### 5. Blood Request Management
Full oversight of all patient blood requests.
- **List View**: All requests with status filter; overdue requests are auto-expired on load.
- **View Detail**: Full request info, matching donors (tiered: township > region > all), competing requests, and assigned/accepted donor info.
- **Assign Donor**: Manually assign a donor; the request moves to ASSIGNED (42). If the donor is already assigned to a lower-urgency request, that donor is auto-reassigned; equal-or-higher urgency assignments are blocked.
- **Bulk Assign**: Select and assign multiple donors (`request_donors` rows with response PENDING).
- **Notify Donors**: Send notifications to selected donors.
- **Complete Request**: Marks the request COMPLETED (9) and records the donation.
- **Delete Request**: Remove a request (cascading delete of related records).
- **Reset to Pending**: Returns a DECLINED request back to PENDING.
- **Donation History**: Global donation history view.

#### 6. Hospital Stock Request Management
Administrators review and act on hospital stock requests.
- **List View**: Overview of all hospital requests with status.
- **Accept**: Request must be PENDING; allocates `min(requested, available)` bags via FEFO; status becomes COMPLETED if fully covered, otherwise PARTIAL; hospital is notified.
- **Decline**: Request must be PENDING; moved to DECLINED with no allocation; hospital is notified.
- **Provide More**: Request must be PARTIAL with pending quantity > 0; allocates `min(pending, available)` bags; marks COMPLETED when pending reaches zero; hospital is notified.
- **Pending Count**: JSON endpoint for the navigation badge.

#### 7. Roles & Permissions Management
RBAC configuration.
- **Roles List**: View all user types (Admin, Donor, Patient, Hospital).
- **Edit Role**: View role details.
- **Update Permissions**: Toggle permissions per role (stored in `user_type_permissions`).

#### 8. Reports & Export
Administrators can view and export filtered reports.
- **Report Types**: Donors, Patients, Blood Requests, Donations (with donor/hospital tabs).
- **Filters**: Keyword, status, urgency, blood group, location, date range (from/to).
- **On-Screen Table**: Same filters render the browser table.
- **Export PDF**: Dompdf generator streams `{report}_YYYY-MM-DD.pdf`.
- **Export Excel**: Native XLSX generator streams `{report}_YYYY-MM-DD.xlsx`.
- The exported file always matches the on-screen filter.

#### 9. Admin Profile & Notifications
Admins can manage their own account.
- Profile viewing and editing (username, email, phone) with password change.
- Notifications page with unread count and mark-as-read actions.

---

### Module: Central Blood Stock & Bag Accounting

#### 1. Bag-Level Inventory Tracking
The system tracks inventory at the individual bag level.
- Each bag has a blood group, collection date, expiry date (collection + 42 days), and status (AVAILABLE / ALLOCATED / EXPIRED).
- The `blood_stock` table maintains per-group available counts.
- New donations call `recordBags()`, which inserts one row per bag and increments the per-group count.

#### 2. FEFO Allocation
Stock is issued on a First-Expiry-First-Out basis.
- `decreaseIfAvailable()` selects the oldest-expiring AVAILABLE bags, marks them ALLOCATED (optionally tied to a hospital request), and decrements the per-group count.
- The operation runs in a transaction and rolls back if insufficient bags are available.

#### 3. Expiry & Disposal
Expired inventory is automatically handled.
- `expireDueBags()` marks AVAILABLE bags past their expiry as EXPIRED and decrements the per-group count.
- Admins can dispose of EXPIRED rows from the blood-stock screen (`disposeExpiredBags`).
- Dashboards surface expiring-soon and expired counts.

#### 4. Stock Adjustment Ledger
Every stock change is audited.
- The `blood_stock_adjustments` table records blood group, delta, reason (DONATION / ALLOCATION / EXPIRED), and who performed it.
- Recent adjustments are shown on hospital and admin dashboards.

---

### Module: Notification System

#### 1. In-App Notifications
Notifications are stored in the `notifications` table and displayed inside the portal.
- **Types**: Blood Request, Approval, Reminder, Profile Update, Warning, General, User Action, Admin Action, Stock Request.
- **Unread Count**: AJAX endpoints poll and display badge counts.
- **Mark Read**: Single notification or mark-all-as-read.
- **View All**: Notification page with full history.
- **Recipients by Event**:
  - New patient request → all admins and matching donors.
  - Donor assigned by admin → admins, donor, patient.
  - Donor accepts request → admins, donor, patient.
  - Request completed → admins, patient, donor.
  - Request cancelled/expired → patient and admins.
  - Standalone donation → donor and all admins.
  - Hospital request submitted → hospital and admins.
  - Stock request approved/declined/top-up → hospital.

#### 2. Email Notifications
Email serves as an external communication channel for important events.
- **Types of Email Alerts**:
  - Email verification codes and password-reset OTPs.
  - Donor assignment mails to donors and patients.
  - Donor acceptance mails to patients and admins.
  - Donation-completed mails to donors and patients.
  - Request-expired notices to patients and admins.
- Emails are optional and guarded by the presence of the recipient's email address.

#### 3. Activity Logging
Key system events are recorded for audit and dashboards.
- Logs user actions, request actions, donation events, and stock events.
- Representative actions: `USER_LOGIN`, `BLOOD_REQUEST_CREATED`, `DONATION_RECORDED`, `REQUEST_ACCEPTED`, `STOCK_REQUEST_SUBMITTED`, `STOCK_REQUEST_APPROVED`, `STOCK_REQUEST_DECLINED`, `STOCK_REQUEST_TOP_UP`.

---

### Module: System & Security

#### 1. Role-Based Access Control (RBAC)
- Permissions are defined per user type: `dashboard.view`, `blood_request.create`, `donation.create`, `hospital_request.create`, `report.view`, etc.
- Middleware (`PermissionMiddleware`) checks permissions on every protected route.
- Permission middleware supports parameters, e.g., `can:blood_request.view_matching`.
- Dynamic permission management via the admin interface.

#### 2. Session Security
- PHP sessions with `session_regenerate_id()` on login.
- Logout destroys the session.
- Role verification on every protected request.

#### 3. Google reCAPTCHA v2
- Optional integration on the Login and Registration pages (enabled via environment keys).
- Server-side verification calls the Google `siteverify` endpoint.
- Prevents automated bot registrations and brute-force login attempts.

#### 4. Google OAuth 2.0
- Social login option on the login page.
- Auto-links to existing email accounts.
- New users are prompted to select a role and complete registration.

---

## 4. Functional Requirements (Processes)

FR1: The system shall allow guests to register as a Patient or Donor with name, email, phone, password, blood group, and address.
FR2: The system shall verify email via a one-time code (OTP) before fully activating new accounts.
FR3: The system shall integrate Google reCAPTCHA v2 on registration and login when configured.
FR4: The system shall authenticate users via email and password using bcrypt hashing.
FR5: The system shall support Google OAuth 2.0 login with role selection for new users.
FR6: The system shall redirect authenticated users to their role-specific dashboard.
FR7: The system shall allow users to reset forgotten passwords via an OTP flow.
FR8: The system shall require Donors to complete a profile (date of birth, weight, state/region, township) before use.
FR9: The system shall evaluate donor eligibility based on age (18–65) and weight (≥ 50 kg).
FR10: The system shall allow Patients to create blood requests specifying blood group, units, hospital, urgency, and location.
FR11: The system shall prevent a Patient from having more than one open (PENDING/ASSIGNED) request at a time.
FR12: The system shall allow Patients to view, filter, cancel, and reactivate their own requests.
FR13: The system shall allow Patients to download a PDF receipt for a request.
FR14: The system shall allow Patients to search for donors by blood group and location.
FR15: The system shall display blood requests matching a Donor's blood group, prioritized by same township → same region → any region.
FR16: The system shall allow Donors to accept or decline blood requests.
FR17: The system shall enforce the 3-month donation cooldown and a single active accepted request per Donor.
FR18: The system shall allow Donors to record standalone donations of up to 2 bags.
FR19: The system shall add donated bags to central stock with a 42-day shelf life.
FR20: The system shall maintain a donation history for each Donor, including request-linked and standalone donations.
FR21: The system shall allow Donors to view and download a certificate for completed donations.
FR22: The system shall allow Hospitals to view central blood stock levels.
FR23: The system shall allow Hospitals to submit stock requests specifying blood group and quantity.
FR24: The system shall allow Hospitals to view their own requests and pending shortfalls.
FR25: The system shall provide an Admin dashboard with summary stats, charts, recent requests, and an activity feed.
FR26: The system shall allow Admins to create, read, update, and soft-delete users.
FR27: The system shall allow Admins to create and manage hospital accounts.
FR28: The system shall allow Admins to view, assign (single and bulk), notify, complete, delete, and reset blood requests.
FR29: The system shall allow Admins to approve, decline, and provide more bags for hospital stock requests.
FR30: The system shall track blood bags individually and allocate stock on a First-Expiry-First-Out basis.
FR31: The system shall mark expired bags as EXPIRED and allow Admins to dispose of them.
FR32: The system shall record every stock change in a stock adjustment ledger.
FR33: The system shall allow Admins to manage roles and permissions dynamically.
FR34: The system shall allow Admins to view and export reports (donors, patients, blood requests, donations) to PDF and Excel.
FR35: The system shall notify users via in-app notifications for assignments, donations, stock events, and system events.
FR36: The system shall allow users to mark notifications as read, individually or all at once.
FR37: The system shall log key system events for audit and dashboard display.
FR38: The system shall allow users to update their profile (name, email, phone, blood group, address, password).

## 5. Non-Functional Requirements

NFR1: Performance: The system shall handle concurrent access by multiple users across all roles without significant latency.
NFR2: Scalability: The modular Clean Architecture (Repository pattern, dependency injection) supports easy addition of new features and modules.
NFR3: Security: Passwords hashed with bcrypt; session-based authentication; RBAC enforced via middleware; SQL injection prevented via prepared statements.
NFR4: Usability: Responsive UI via Tailwind CSS; mobile-compatible layouts; interactive charts; role-specific dashboards.
NFR5: Availability: Expected 99.9% uptime in production deployment.
NFR6: Maintainability: Separation of concerns (Domain, Application, Infrastructure, Presentation); dependency injection; PSR-4 autoloading.
NFR7: Reliability: Transactional stock accounting prevents inconsistent inventory; foreign key constraints maintain data integrity; expired requests are auto-handled.
NFR8: Auditability: Every stock change and key user action is recorded in the activity log and stock adjustment ledger.

## 6. External Interfaces

### 6.1 Hardware Interfaces
- **Client**: Standard PC, laptop, tablet, or smartphone with a modern web browser (Chrome, Firefox, Edge, Safari).
- **Server**: XAMPP stack (Apache, MySQL, PHP 8+) on Windows or Linux.

### 6.2 Software Interfaces
- **Frontend**: HTML5, Tailwind CSS, Chart.js, Font Awesome.
- **Backend**: PHP 8+ with Clean Architecture / DDD.
- **Database**: MySQL (InnoDB, UTF-8).
- **Web Server**: Apache 2.4+ (mod_rewrite enabled).
- **PDF Generation**: Dompdf.
- **Excel Generation**: Native XLSX generator (no third-party library).
- **Email**: PHP `mail()` or SMTP for OTP and notification delivery.
- **External APIs**: Google OAuth 2.0, Google reCAPTCHA v2 (optional).

### 6.3 Database Tables (Key)
| Table | Purpose |
|---|---|
| `users` | All system users with role, blood group, verification, and cooldown data |
| `donors` | Donor-specific fields (DOB, weight, location) |
| `hospitals` | Hospital-specific profile fields |
| `user_types` | Role definitions (Admin, Donor, Patient, Hospital) |
| `permissions` | Available permission keys |
| `user_type_permissions` | Role ↔ permission mapping |
| `blood_requests` | Patient blood request records with status and donor assignment |
| `request_donors` | Many-to-many: requests ↔ donors with response status |
| `donation_history` | Donation records (request-linked and standalone) |
| `hospital_blood_requests` | Hospital stock requests with requested/allocated/pending quantities |
| `blood_stock` | Per-group available bag counts |
| `blood_bags` | Individual bag records with expiry and allocation status |
| `blood_stock_adjustments` | Audit ledger of stock changes |
| `master_data` | Lookup table for statuses, blood groups, urgency, and notification types |
| `notifications` | In-app user notifications |
| `activity_logs` | System event audit trail |
