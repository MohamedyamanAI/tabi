# Privacy Policy

**Effective date:** February 23, 2026

Tabi ("we", "us", "our") is a personal project that operates the Tabi time-tracking platform. This policy explains what data we collect, why we collect it, and how we handle it.

## 1. Data We Collect

### Account Information
When you register, we collect your name, email address, and password (stored hashed using bcrypt). Organization administrators may also provide team member details such as names and email addresses.

### Time-Tracking Data
We record time entries, project and task associations, tags, and billable status as created by users.

### Screenshots & Activity Data
When screenshot monitoring is enabled by an organization administrator, the Tabi desktop application periodically captures full-screen screenshots of the user's active display during tracked time periods. We also collect:

- **Activity levels:** keyboard and mouse activity measured as percentages (we do **not** log individual keystrokes or record mouse coordinates).
- **Idle detection:** periods of inactivity are detected locally on the user's device and reported as idle time.
- **Tracking status:** whether the user is actively tracking time.

Screenshots are captured **only** while time tracking is running and the screenshot feature is enabled. The desktop application shows a visible tray icon and status indicator whenever tracking and screenshot capture are active. Users can stop tracking at any time to stop all data collection. Screenshots and activity data are accessible only to authorized members of the user's organization (administrators and managers with appropriate permissions).

### Technical Data
We automatically collect IP addresses, browser type, operating system, and device identifiers for security, troubleshooting, and abuse prevention.

## 2. Legal Basis for Processing (GDPR)

If you are located in the European Economic Area (EEA), United Kingdom, or Switzerland, we process your personal data on the following legal bases:

- **Contract performance:** processing necessary to provide the Service you signed up for (account data, time entries).
- **Legitimate interest:** security monitoring, fraud prevention, and service improvement.
- **Consent:** screenshot and activity monitoring, where your organization has enabled these features and you have been informed. You may withdraw consent at any time by stopping the desktop tracker or requesting your administrator disable monitoring.

## 3. How We Use Your Data

- **Providing the Service:** time tracking, reporting, invoicing, and team management.
- **Screenshots & activity monitoring:** to help organizations verify work and measure productivity. This data is only collected when an organization administrator has enabled these features and the desktop application is actively tracking time.
- **Security:** detecting unauthorized access and abuse prevention.
- **Communication:** account-related notifications, support, and service updates. We do not send marketing emails.

## 4. Data Sharing

We do not sell, rent, or trade your personal data. We may share data with:

- **Infrastructure providers** (hosting, object storage, email delivery) that process data on our behalf. These providers are contractually bound to process data only as instructed and to maintain appropriate security measures.
- **Organization administrators** within your organization who have access to time entries, screenshots, activity data, and reports for members of their organization.
- **Law enforcement** only when required by a valid legal order under applicable law.

We do not use third-party analytics, advertising networks, or data brokers.

## 5. International Data Transfers

Your data may be processed in countries outside your country of residence. Where data is transferred outside the EEA, we rely on appropriate safeguards such as the service provider's compliance with recognized data protection frameworks.

## 6. Data Storage & Security

- Data at rest is stored in encrypted databases.
- Screenshots are stored in encrypted object storage.
- All data in transit is protected with TLS (HTTPS).
- Passwords are hashed using bcrypt and are never stored in plain text.
- Access to production systems is restricted and logged.

## 7. Data Retention

- **Account data:** retained while your account is active and for 30 days after account deletion to allow recovery.
- **Screenshots:** retained according to the organization's configured retention policy. Organization administrators may delete screenshots at any time.
- **Time-tracking data:** retained while your account is active. You may export your data at any time.
- **Technical logs:** retained for up to 90 days for security and debugging purposes.

After the retention period, data is permanently deleted and cannot be recovered.

## 8. Your Rights

### For all users
- Access, correct, or delete your personal data.
- Export your data in a machine-readable format (JSON/CSV).
- Withdraw consent for optional data collection (screenshots and activity monitoring) at any time by stopping the desktop tracker.

### Additional rights under GDPR (EEA/UK/Swiss users)
- **Right to erasure:** request deletion of your personal data.
- **Right to restriction:** request we limit how we process your data.
- **Right to object:** object to processing based on legitimate interest.
- **Right to portability:** receive your data in a structured, machine-readable format.
- **Right to lodge a complaint:** with your local data protection authority.

### Additional rights under CCPA (California residents)
- **Right to know:** what personal information we collect and how it is used.
- **Right to delete:** request deletion of your personal information.
- **Right to opt out:** we do not sell personal information, so this right does not apply.
- **Non-discrimination:** we will not discriminate against you for exercising your rights.

To exercise any of these rights, contact your organization administrator or reach out to us through the contact method listed below.

## 9. Children's Privacy

Tabi is not intended for use by anyone under the age of 16. We do not knowingly collect personal data from children under 16. If we become aware that we have collected data from a child under 16, we will delete it promptly.

## 10. Cookies

We use only **essential cookies** for session management and authentication. These cookies are strictly necessary for the Service to function. We do not use third-party advertising, tracking, or analytics cookies. No cookie consent banner is required because we do not use non-essential cookies.

## 11. Changes to This Policy

We may update this policy from time to time. We will notify registered users of material changes via email at least 14 days before they take effect. The "Effective date" at the top of this page indicates when the policy was last revised.

## 12. Contact

If you have questions about this privacy policy or wish to exercise your data rights, please contact us through the Tabi application or by opening an issue on our [GitHub repository](https://github.com/MohamedyamanAI/tabi).
