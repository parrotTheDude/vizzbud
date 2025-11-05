@extends('layouts.vizzbud')

@section('title', 'Privacy Policy | Vizzbud')
@section('meta_description', 'Learn how Vizzbud collects, uses, and protects your personal information, including dive logs and analytics data.')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-16 prose prose-invert">
  <h1 class="text-3xl font-bold mb-4">Privacy Policy</h1>
  <p><strong>Last updated:</strong> {{ now()->format('F j, Y') }}</p>

  <p>
    This Privacy Policy explains how <strong>Vizzbud</strong> (“we”, “us”, or “our”) collects, uses, and protects personal
    information when you use our website and services at <a href="https://vizzbud.com">vizzbud.com</a>.
    Vizzbud is owned and operated by <strong>Bowerman Digital</strong>, based in New South Wales, Australia.
  </p>

  <hr>

  <h2>1. Information We Collect</h2>
  <p>We collect only the information necessary to provide and improve our services:</p>

  <ul>
    <li><strong>Account Information:</strong> Your name, email address, and password (stored securely using encryption and hashing).</li>
    <li><strong>Dive Logs:</strong> Dive titles, notes, and related metadata you enter into Vizzbud (encrypted in our database).</li>
    <li><strong>Technical Data:</strong> IP address, browser type, device information (used for security and analytics).</li>
    <li><strong>Analytics:</strong> Privacy-friendly metrics such as page visits and referrers, collected via <a href="https://simpleanalytics.com" target="_blank">Simple Analytics</a> (no cookies or personal tracking).</li>
  </ul>

  <h2>2. How We Use Your Information</h2>
  <p>We use your data to:</p>
  <ul>
    <li>Operate and maintain your account.</li>
    <li>Store and display your dive logs securely.</li>
    <li>Send necessary account or verification emails (via <a href="https://postmarkapp.com" target="_blank">Postmark</a>).</li>
    <li>Monitor performance and improve the user experience.</li>
    <li>Detect and prevent unauthorised access or abuse of the platform.</li>
  </ul>

  <h2>3. Data Storage and Security</h2>
  <p>
    All data is stored securely on servers managed by our hosting provider in Australia (or equivalent secure data centres).
    Sensitive data such as passwords and dive log notes are encrypted using industry-standard AES-256 encryption.
  </p>
  <p>
    Access to databases and server resources is restricted to authorised personnel only.
  </p>

  <h2>4. Sharing and Third Parties</h2>
  <p>We do not sell or rent your personal information. We only share data with trusted services necessary for Vizzbud to function:</p>
  <ul>
    <li><strong>Postmark:</strong> For sending transactional emails such as verification or reset links.</li>
    <li><strong>Mapbox:</strong> For displaying interactive dive site maps.</li>
    <li><strong>Simple Analytics:</strong> For anonymous site usage insights (no cookies or personal identifiers).</li>
  </ul>
  <p>Each of these services complies with GDPR and Australian privacy standards.</p>

  <h2>5. Cookies and Tracking</h2>
  <p>
    Vizzbud does not use advertising or tracking cookies. We use Simple Analytics, which collects anonymous data without storing
    personal information, cookies, or IP-based fingerprints.
  </p>

  <h2>6. Data Retention</h2>
  <p>
    We retain your data for as long as you maintain an active account. You can delete your account at any time,
    which permanently removes your personal data and dive logs from our systems within 30 days.
  </p>

  <h2>7. Your Rights</h2>
  <p>You have the right to:</p>
  <ul>
    <li>Access or download your personal data.</li>
    <li>Request correction or deletion of inaccurate information.</li>
    <li>Withdraw consent or close your account.</li>
  </ul>
  <p>
    To make a privacy-related request, contact us via email at
    <a href="mailto:support@vizzbud.com">support@vizzbud.com</a>.
  </p>

  <h2>8. International Data Transfers</h2>
  <p>
    If you access Vizzbud from outside Australia, your data may be transferred to and processed on servers located in other
    countries that maintain similar data protection standards.
  </p>

  <h2>9. Updates to This Policy</h2>
  <p>
    We may update this Privacy Policy from time to time to reflect changes in legal, technical, or operational requirements.
    Updates will be posted on this page with a revised “Last updated” date.
  </p>

  <h2>10. Contact</h2>
  <p>
    If you have questions or concerns about this Privacy Policy or your data, please contact:
  </p>

  <p>
    <strong>Bowerman Digital</strong><br>
    Email: <a href="mailto:support@vizzbud.com">support@vizzbud.com</a><br>
    Sydney, NSW, Australia
  </p>
</section>
@endsection