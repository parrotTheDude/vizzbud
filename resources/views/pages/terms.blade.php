@extends('layouts.vizzbud')

@section('title', 'Terms of Service | Vizzbud')
@section('meta_description', 'Review the terms and conditions for using Vizzbud, including account responsibilities, user content, and liability disclaimers.')

@section('content')
<section class="max-w-4xl mx-auto px-6 py-16 prose prose-invert">
  <h1 class="text-3xl font-bold mb-4">Terms of Service</h1>
  <p><strong>Last updated:</strong> {{ now()->format('F j, Y') }}</p>

  <p>
    Welcome to <strong>Vizzbud</strong>. These Terms of Service (“Terms”) govern your access to and use of our website,
    platform, and related services available at <a href="https://vizzbud.com">vizzbud.com</a> (“Service”).
    By creating an account or using the Service, you agree to these Terms.
  </p>

  <p>
    Vizzbud is owned and operated by <strong>Bowerman Digital</strong>, based in New South Wales, Australia.
    If you do not agree to these Terms, please discontinue use of Vizzbud.
  </p>

  <hr>

  <h2>1. Use of the Service</h2>
  <p>
    Vizzbud provides tools for divers to log dives, explore dive sites, and view live environmental conditions.
    You must be at least 16 years old to use Vizzbud and create an account.
  </p>

  <p>
    You agree to use Vizzbud only for lawful purposes and in accordance with these Terms.
    You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
  </p>

  <h2>2. Account Registration</h2>
  <p>
    When creating an account, you must provide accurate and complete information.
    You are responsible for maintaining the confidentiality of your password and agree to notify us immediately
    if you suspect any unauthorised access to your account.
  </p>

  <h2>3. User-Generated Content</h2>
  <p>
    You retain full ownership of the content you create on Vizzbud (including dive logs, notes, or uploaded media),
    but you grant us a limited, non-exclusive license to host, display, and process this content as part of operating the Service.
  </p>
  <p>
    You are responsible for ensuring that your content does not infringe on the rights of others or violate any applicable laws.
    We reserve the right to remove or restrict access to any content that breaches these Terms or appears harmful, abusive, or unlawful.
  </p>

  <h2>4. Data Privacy</h2>
  <p>
    We take your privacy seriously. Please review our <a href="{{ route('privacy') }}">Privacy Policy</a> for details
    on how we collect, store, and protect your information.
  </p>

  <h2>5. Service Availability and Updates</h2>
  <p>
    We aim to keep Vizzbud available at all times, but we may need to perform maintenance or updates periodically.
    We may modify or discontinue parts of the Service without prior notice.
  </p>

  <h2>6. Third-Party Services</h2>
  <p>
    Vizzbud integrates with external services such as Mapbox (for dive maps), Postmark (for transactional emails),
    and Simple Analytics (for privacy-focused site metrics).
    We are not responsible for the content, availability, or security of these third-party services.
  </p>

  <h2>7. Disclaimer of Liability</h2>
  <p>
    Vizzbud provides environmental data and dive-site information “as is” for general reference only.
    Dive conditions can change rapidly, and local assessments should always take priority.
  </p>
  <p>
    You agree that your decision to dive based on information from Vizzbud is entirely at your own risk.
    Vizzbud and Bowerman Digital accept no responsibility for injury, damage, or loss
    resulting from the use or misuse of this information.
  </p>

  <h2>8. Intellectual Property</h2>
  <p>
    All content and code on Vizzbud (including the logo, brand identity, design, and software) are the property of
    Bowerman Digital or its licensors. You may not copy, modify, or distribute any part of Vizzbud
    without written permission.
  </p>

  <h2>9. Termination</h2>
  <p>
    We may suspend or terminate your account if you breach these Terms, misuse the platform, or engage in activities
    that could harm other users or the Service.
  </p>
  <p>
    You may close your account at any time. Upon deletion, your personal data and dive logs will be removed from our systems in accordance with our Privacy Policy.
  </p>

  <h2>10. Limitation of Liability</h2>
  <p>
    To the maximum extent permitted by law, Bowerman Digital, its directors, and affiliates are not liable for any
    indirect, incidental, or consequential damages arising from your use of the Service.
  </p>
  <p>
    Our total liability for any claim related to the Service shall not exceed the total amount (if any)
    you have paid to us for using Vizzbud in the preceding 12 months.
  </p>

  <h2>11. Governing Law</h2>
  <p>
    These Terms are governed by and interpreted in accordance with the laws of <strong>New South Wales, Australia</strong>.
    You agree to submit to the exclusive jurisdiction of the courts located in New South Wales.
  </p>

  <h2>12. Changes to These Terms</h2>
  <p>
    We may update these Terms from time to time. Any changes will take effect immediately when published on this page.
    Continued use of Vizzbud constitutes acceptance of the revised Terms.
  </p>

  <h2>13. Contact</h2>
  <p>
    If you have questions or feedback about these Terms, please contact:
  </p>

  <p>
    <strong>Bowerman Digital</strong><br>
    Email: <a href="mailto:support@vizzbud.com">support@vizzbud.com</a><br>
    Sydney, NSW, Australia
  </p>
</section>
@endsection