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
					<div id="f_reporting" class="feature-input" aria-readonly="true" role="textbox">Shift coverage reports (e.g., over/under-staffing).<br>Attendance trends (late arrivals, absences, overtime).<br>Exportable data (PDF, Excel) for HR and payroll.</div>
				</div>

				<div class="feature-box">
					<label for="f_attendance"><strong>Digital Attendance Tracking</strong></label>
					<div id="f_attendance" class="feature-input" aria-readonly="true" role="textbox">Digital signature check-ins to eliminate manual errors.<br>Real-time presence monitoring with wifi validation.<br>Attendance reports for payroll and compliance.</div>
				</div>

				<div class="feature-box">
					<label for="f_documents"><strong>Document Management &amp; Compliance</strong></label>
					<div id="f_documents" class="feature-input" aria-readonly="true" role="textbox">Secure upload, storage, and sharing of employee documents:<br>Medical certificates<br>Employment contracts<br>Automated reminders for expiring documents.</div>
				</div>

				<div class="feature-box">
					<label for="f_roles"><strong>User Roles &amp; Access Control</strong></label>
					<div id="f_roles" class="feature-input" aria-readonly="true" role="textbox">Multiple user roles with custom permissions<br>Granular access levels to protect sensitive data.<br>Multi-Device Access : Fully responsive design dedicated mobile and tablet.</div>
				</div>

				<div class="feature-box">
					<label for="f_security"><strong>Security &amp; Compliance</strong></label>
					<div id="f_security" class="feature-input" aria-readonly="true" role="textbox">End-to-end encryption for all data.<br>GDPR-compliant (data protection for employees).<br>Two-factor authentication (2FA) for admin accounts.<br>Audit logs to track all actions.</div>
				</div>

				<div class="feature-box">
					<label for="f_shifts"><strong>Smart Shift Management</strong></label>
					<div id="f_shifts" class="feature-input" aria-readonly="true" role="textbox">Auto-fill shifts based on employee availability, skills, and workload.<br>Real-time conflict detection to avoid overbooking or clashes.<br>Shift templates for recurring schedules (e.g., morning, afternoon, night shifts).</div>
				</div>
			</div>
		</section>

	</section>

	
</div>
