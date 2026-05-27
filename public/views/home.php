<!-- Page d'accueil : présente les fonctionnalités principales du projet. -->
<?php $currentUser = currentUser(); ?>
<div class="home-page">
	<h1>Welcome to StaffEase Pro</h1>
	<p>A Revolutionary Shift Management System for the Hospitality Industry</p>
	<section class="home-intro">
		<h2>Simplify, Optimize, and Automate Your Workforce Management</h2>

		<p>
			StaffEase Pro is the ultimate web and mobile application designed to streamline workforce management in hotels, resorts, and hospitality businesses.
			Say goodbye to manual scheduling, errors, and inefficiencies—our platform empowers you to automate shifts, track attendance, manage documents, and ensure seamless communication, all from a single, intuitive dashboard.
		</p>
		<p>
			Built for hotel managers, receptionists, housekeeping staff, and administrators, StaffEase Pro combines cutting-edge technology with user-friendly design to deliver a scalable, secure, and efficient solution for your business.
		</p>

		<h3>🎯 Who Is StaffEase Pro For?</h3>
		<ul>
			<li>✅ Hotel Managers – Optimize staffing and reduce costs.</li>
			<li>✅ HR Departments – Streamline onboarding and compliance.</li>
			<li>✅ Department Heads (Housekeeping, F&B, etc.) – Manage schedules effortlessly.</li>
			<li>✅ Employees – Clock in/out, request changes, and access documents on the go.</li>
			<li>✅ Super Admins – Get full control over your workforce management system.</li>
		</ul>


		<!-- Editable feature boxes: allow entering texts for each area -->
		<section class="editable-features">
			<div class="editable-grid">
				<div class="feature-box">
					<label for="f_reporting"><strong>Reporting &amp; Analytics</strong></label>
					<textarea id="f_reporting" class="feature-input" readonly>Shift coverage reports (e.g., over/under-staffing).
Attendance trends (late arrivals, absences, overtime).
Exportable data (PDF, Excel) for HR and payroll.</textarea>
				</div>

				<div class="feature-box">
					<label for="f_attendance"><strong>Digital Attendance Tracking</strong></label>
					<textarea id="f_attendance" class="feature-input" readonly>Digital signature check-ins to eliminate manual errors.
Real-time presence monitoring with wifi validation.
Attendance reports for payroll and compliance.</textarea>
				</div>

				<div class="feature-box">
					<label for="f_documents"><strong>Document Management &amp; Compliance</strong></label>
					<textarea id="f_documents" class="feature-input" readonly>Secure upload, storage, and sharing of employee documents:
Medical certificates
Employment contracts
Automated reminders for expiring documents.</textarea>
				</div>

				<div class="feature-box">
					<label for="f_roles"><strong>User Roles &amp; Access Control</strong></label>
					<textarea id="f_roles" class="feature-input" readonly>Multiple user roles with custom permissions
Granular access levels to protect sensitive data.
Multi-Device Access : Fully responsive design dedicated mobile and tablet.</textarea>
				</div>

				<div class="feature-box">
					<label for="f_security"><strong>Security &amp; Compliance</strong></label>
					<textarea id="f_security" class="feature-input" readonly>End-to-end encryption for all data.
GDPR-compliant (data protection for employees).
Two-factor authentication (2FA) for admin accounts.
Audit logs to track all actions.</textarea>
				</div>

				<div class="feature-box">
					<label for="f_shifts"><strong>Smart Shift Management</strong></label>
					<textarea id="f_shifts" class="feature-input" readonly>Auto-fill shifts based on employee availability, skills, and workload.
Real-time conflict detection to avoid overbooking or clashes.
Shift templates for recurring schedules (e.g., morning, afternoon, night shifts).</textarea>
				</div>
			</div>
		</section>

	</section>

	
</div>
