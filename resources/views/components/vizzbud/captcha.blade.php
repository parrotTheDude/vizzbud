{{-- 🧠 Friendly Captcha (Reusable Vizzbud Component) --}}
<div class="flex justify-center mt-4">
  <div class="frc-captcha vizzbud-captcha"
       data-sitekey="{{ $sitekey }}"
       data-start="auto"></div>
</div>

{{-- Load Friendly Captcha script only once --}}
@once
  <script type="module"
          src="https://unpkg.com/@friendlycaptcha/sdk@0.1.31/site.min.js"
          async defer></script>
  <script nomodule
          src="https://unpkg.com/@friendlycaptcha/sdk@0.1.31/site.compat.min.js"
          async defer></script>

  <style>
    /* 🌊 Vizzbud Captcha Styling */
    .vizzbud-captcha {
      --frc-primary-color: #22d3ee;
      --frc-primary-hover-color: #2dd4bf;
      --frc-bg-color: rgba(255, 255, 255, 0.05);
      --frc-bg-hover-color: rgba(255, 255, 255, 0.08);
      --frc-border-color: rgba(255, 255, 255, 0.15);
      --frc-border-hover-color: rgba(34, 211, 238, 0.3);
      --frc-text-color: rgba(255, 255, 255, 0.9);

      --frc-checkbox-bg: rgba(34, 211, 238, 0.15);
      --frc-checkbox-bg-hover: rgba(34, 211, 238, 0.25);
      --frc-checkbox-border: rgba(34, 211, 238, 0.4);
      --frc-checkbox-border-hover: rgba(34, 211, 238, 0.6);
      --frc-progress-icon-color: #2dd4bf;

      --frc-error-text-color: #fca5a5;
      --frc-error-subtext-color: rgba(255, 255, 255, 0.6);
      --frc-error-bg-color: rgba(239, 68, 68, 0.15);
      --frc-error-bg-hover-color: rgba(239, 68, 68, 0.25);
      --frc-error-border-color: rgba(239, 68, 68, 0.4);
      --frc-error-border-hover-color: rgba(239, 68, 68, 0.6);
      --frc-error-icon-color: #ef4444;

      border-radius: 0.75rem;
      border: 1px solid var(--frc-border-color);
      box-shadow: 0 0 10px rgba(34, 211, 238, 0.15);
      backdrop-filter: blur(12px);
      padding: 0.5rem;
      transition: all 0.3s ease;
      transform: scale(0.9);
      transform-origin: center;
      opacity: 0.95;
      animation: fadeIn 0.5s ease forwards;
    }

    .vizzbud-captcha:hover {
      border-color: var(--frc-border-hover-color);
      box-shadow: 0 0 20px rgba(34, 211, 238, 0.25);
      opacity: 1;
      transform: scale(1);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 0.95; transform: scale(0.9); }
    }
  </style>
@endonce