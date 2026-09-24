// window.SITE is written by build.py from site.config.json
const SITE = window.SITE || {};
document.documentElement.classList.add("js");

// Scroll reveals
const revealEls = document.querySelectorAll(".reveal");
if ("IntersectionObserver" in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); }
    });
  }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
  revealEls.forEach((el) => io.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add("in"));
}

// Hero drone video: only load and play when motion and data use are welcome.
const video = document.getElementById("hero-video");
const toggle = document.getElementById("video-toggle");
if (video) {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const saveData = navigator.connection && navigator.connection.saveData;
  if (!reduceMotion && !saveData) {
    video.preload = "auto";
    video.play().then(() => { if (toggle) toggle.hidden = false; }).catch(() => {});
  }
  if (toggle) {
    toggle.addEventListener("click", () => {
      const pause = !video.paused;
      if (pause) video.pause(); else video.play();
      toggle.setAttribute("aria-pressed", String(pause));
      toggle.querySelector("span").textContent = pause ? "Play video" : "Pause video";
      toggle.querySelector("i").className = `ph ${pause ? "ph-play" : "ph-pause"}`;
    });
  }
}

// Lead form
const form = document.getElementById("lead-form");
const success = document.getElementById("form-success");
const successText = document.getElementById("success-text");
const submitBtn = form.querySelector('button[type="submit"]');

const rules = {
  name: (v) => (v.trim().length < 2 ? "Please enter your name." : ""),
  phone: (v) => (v.replace(/\D/g, "").length < 10 ? "Please enter a 10-digit phone number." : ""),
  address: (v) => (v.trim().length < 5 ? "Please enter the address or ZIP code." : ""),
};

function validate(input) {
  const msg = rules[input.name](input.value);
  input.closest(".field").classList.toggle("invalid", Boolean(msg));
  input.setAttribute("aria-invalid", String(Boolean(msg)));
  document.getElementById(`e-${input.name}`).textContent = msg;
  return !msg;
}

Object.keys(rules).forEach((name) => {
  const input = form.elements[name];
  input.addEventListener("blur", () => { if (input.value) validate(input); });
  input.addEventListener("input", () => { if (input.closest(".field").classList.contains("invalid")) validate(input); });
});

function showSuccess(message) {
  if (message) successText.textContent = message;
  form.hidden = true;
  success.hidden = false;
  success.focus();
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const inputs = Object.keys(rules).map((n) => form.elements[n]);
  const results = inputs.map(validate);
  const bad = inputs[results.indexOf(false)];
  if (bad) { bad.focus(); return; }

  const data = Object.fromEntries(new FormData(form));

  if (SITE.endpoint) {
    submitBtn.setAttribute("aria-busy", "true");
    try {
      const res = await fetch(SITE.endpoint, {
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      if (!res.ok) throw new Error(res.status);
      showSuccess();
    } catch {
      document.getElementById("e-address").textContent = "Something went wrong sending your request. Please call us instead.";
    } finally {
      submitBtn.removeAttribute("aria-busy");
    }
    return;
  }

  if (SITE.email) {
    const body = `Name: ${data.name}\nPhone: ${data.phone}\nAddress: ${data.address}\nService: ${data.service}`;
    window.location.href = `mailto:${SITE.email}?subject=${encodeURIComponent(`Estimate request: ${data.address}`)}&body=${encodeURIComponent(body)}`;
    showSuccess("Your email app should open with the details filled in. Send it and we will be in touch.");
    return;
  }

  showSuccess("Online requests are not set up yet. Please call us to schedule your estimate.");
});
