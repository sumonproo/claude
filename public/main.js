// Business details. Fill these in and the contact block shows them automatically;
// anything left empty stays hidden.
const CONFIG = {
  phone: "",        // e.g. "(555) 201-4418"
  email: "",        // estimate requests are emailed here
  area: "",         // e.g. "Serving Fort Worth and Tarrant County"
  instagram: "kjgutters5",
};

document.documentElement.classList.add("js");

// Contact details from CONFIG
document.querySelectorAll("[data-config]").forEach((li) => {
  const value = CONFIG[li.dataset.config];
  if (!value) return;
  const link = li.querySelector("[data-config-link]");
  const text = li.querySelector("[data-config-text]");
  if (link) {
    const scheme = link.dataset.configLink;
    link.href = scheme === "tel" ? `tel:${value.replace(/[^\d+]/g, "")}` : `mailto:${value}`;
    link.textContent = value;
  }
  if (text) text.textContent = value;
  li.hidden = false;
});

document.getElementById("year").textContent = new Date().getFullYear();

// Mobile menu: morphing burger + staggered overlay
const burger = document.querySelector(".burger");
const menu = document.getElementById("menu");

function setMenu(open) {
  burger.setAttribute("aria-expanded", String(open));
  burger.setAttribute("aria-label", open ? "Close menu" : "Open menu");
  document.body.style.overflow = open ? "hidden" : "";
  if (open) {
    menu.hidden = false;
    requestAnimationFrame(() => menu.classList.add("open"));
  } else {
    menu.classList.remove("open");
    setTimeout(() => { if (!menu.classList.contains("open")) menu.hidden = true; }, 500);
  }
}
burger.addEventListener("click", () => setMenu(burger.getAttribute("aria-expanded") !== "true"));
menu.addEventListener("click", (e) => { if (e.target.closest("a")) setMenu(false); });
document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !menu.hidden) setMenu(false); });
window.matchMedia("(min-width: 1024px)").addEventListener("change", (e) => { if (e.matches) setMenu(false); });

// Scroll reveals
const revealEls = document.querySelectorAll(".reveal");
if ("IntersectionObserver" in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("in");
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: "0px 0px -40px 0px" });
  revealEls.forEach((el) => io.observe(el));
} else {
  revealEls.forEach((el) => el.classList.add("in"));
}

// Finish selector
const preview = document.getElementById("preview");
const previewName = document.getElementById("preview-name");
const previewNote = document.getElementById("preview-note");
const swatches = [...document.querySelectorAll(".swatch")];

function selectSwatch(sw, focus) {
  swatches.forEach((s) => {
    const on = s === sw;
    s.setAttribute("aria-checked", String(on));
    s.tabIndex = on ? 0 : -1;
  });
  if (focus) sw.focus();
  preview.classList.add("is-changing");
  preview.style.setProperty("--c", sw.style.getPropertyValue("--c"));
  setTimeout(() => {
    previewName.textContent = sw.dataset.name;
    previewNote.textContent = sw.dataset.note;
    preview.classList.remove("is-changing");
  }, 260);
}
swatches.forEach((sw, i) => {
  sw.tabIndex = i === 0 ? 0 : -1;
  sw.addEventListener("click", () => selectSwatch(sw, false));
  sw.addEventListener("keydown", (e) => {
    const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
    if (!dir) return;
    e.preventDefault();
    selectSwatch(swatches[(i + dir + swatches.length) % swatches.length], true);
  });
});

// Estimate form
const form = document.getElementById("estimate-form");
const success = document.getElementById("form-success");
const successText = document.getElementById("success-text");

const rules = {
  name: (v) => (v.trim().length < 2 ? "Please enter your name." : ""),
  phone: (v) => (v.replace(/\D/g, "").length < 10 ? "Please enter a phone number we can reach you on." : ""),
  email: (v) => (v && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? "That email address does not look right." : ""),
  address: (v) => (v.trim().length < 5 ? "Please enter the property address." : ""),
};

function validateField(input) {
  const rule = rules[input.name];
  if (!rule) return true;
  const msg = rule(input.value);
  const field = input.closest(".field");
  field.classList.toggle("invalid", Boolean(msg));
  input.setAttribute("aria-invalid", String(Boolean(msg)));
  document.getElementById(`e-${input.name}`).textContent = msg;
  return !msg;
}

Object.keys(rules).forEach((name) => {
  const input = form.elements[name];
  input.addEventListener("blur", () => { if (input.value) validateField(input); });
  input.addEventListener("input", () => { if (input.closest(".field").classList.contains("invalid")) validateField(input); });
});

form.addEventListener("submit", (e) => {
  e.preventDefault();
  const inputs = Object.keys(rules).map((n) => form.elements[n]);
  const results = inputs.map(validateField);
  const firstBad = inputs[results.indexOf(false)];
  if (firstBad) { firstBad.focus(); return; }

  const data = new FormData(form);
  const services = data.getAll("service");
  const body = [
    `Name: ${data.get("name")}`,
    `Phone: ${data.get("phone")}`,
    data.get("email") ? `Email: ${data.get("email")}` : null,
    `Address: ${data.get("address")}`,
    `Services: ${services.length ? services.join(", ") : "Not sure yet"}`,
    data.get("notes") ? `Notes: ${data.get("notes")}` : null,
  ].filter(Boolean).join("\n");

  if (CONFIG.email) {
    const subject = encodeURIComponent(`Estimate request: ${data.get("address")}`);
    window.location.href = `mailto:${CONFIG.email}?subject=${subject}&body=${encodeURIComponent(body)}`;
    successText.textContent = "Your email app should open with the details filled in. Hit send and we will be in touch.";
  } else {
    // No inbox configured yet: copy the details and point to Instagram DMs.
    navigator.clipboard?.writeText(body).catch(() => {});
    successText.innerHTML = `We copied your details. Paste them into a message to <a href="https://ig.me/m/${CONFIG.instagram}" target="_blank" rel="noopener">@${CONFIG.instagram}</a> and we will reply there.`;
  }

  form.hidden = true;
  success.hidden = false;
  success.focus();
});

document.getElementById("form-reset").addEventListener("click", () => {
  form.reset();
  form.hidden = false;
  success.hidden = true;
  form.elements.name.focus();
});
