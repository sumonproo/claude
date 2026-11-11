// Site script (~2 KB). The AI chat is NOT loaded here: chat.js is fetched only when a visitor opens it.
(() => {
  // Mobile menu
  const toggle = document.querySelector(".nav__toggle");
  const links = document.getElementById("nav-links");
  if (toggle && links) {
    toggle.addEventListener("click", () => {
      const open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!open));
      links.classList.toggle("is-open", !open);
    });
  }

  // Guides dropdown (desktop). On mobile the guides are listed inside the menu.
  const menuBtn = document.querySelector(".menu__btn");
  if (menuBtn) {
    const close = () => menuBtn.setAttribute("aria-expanded", "false");
    menuBtn.addEventListener("click", e => {
      e.stopPropagation();
      menuBtn.setAttribute("aria-expanded", String(menuBtn.getAttribute("aria-expanded") !== "true"));
    });
    document.addEventListener("click", e => { if (!e.target.closest(".menu")) close(); });
    document.addEventListener("keydown", e => {
      if (e.key === "Escape" && menuBtn.getAttribute("aria-expanded") === "true") { close(); menuBtn.focus(); }
    });
  }

  // Topic filters (experts directory, blog)
  const filters = document.querySelector(".filters");
  if (filters) {
    const items = [...document.querySelectorAll("[data-topic]")];
    const count = document.querySelector("[data-count]");
    const empty = document.querySelector(".empty");
    filters.addEventListener("click", e => {
      const btn = e.target.closest("[data-filter]");
      if (!btn) return;
      filters.querySelectorAll("[data-filter]").forEach(b => b.setAttribute("aria-pressed", String(b === btn)));
      let shown = 0;
      items.forEach(el => {
        const show = btn.dataset.filter === "all" || el.dataset.topic === btn.dataset.filter;
        el.hidden = !show;
        if (show) shown++;
      });
      if (count) count.textContent = shown;
      if (empty) empty.hidden = shown > 0;
    });
  }

  // Highlight the current section in a guide's contents list
  const tocLinks = [...document.querySelectorAll(".guide__toc a")];
  if (tocLinks.length && "IntersectionObserver" in window) {
    const byId = new Map(tocLinks.map(a => [a.hash.slice(1), a]));
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (en.isIntersecting) {
          tocLinks.forEach(a => a.classList.remove("is-active"));
          byId.get(en.target.id)?.classList.add("is-active");
        }
      });
    }, { rootMargin: "-20% 0px -70% 0px" });
    byId.forEach((_, id) => { const s = document.getElementById(id); if (s) io.observe(s); });
  }

  // Inquiry form: preselect topic from ?topic= or ?expert=, validate.
  // In WordPress this becomes an Elementor Form (or similar) that emails the team.
  const form = document.querySelector("[data-inquiry]");
  if (form) {
    const params = new URLSearchParams(location.search);
    const expertTopic = { ex1: "immex", ex2: "labor", ex3: "corporate", ex4: "real-estate", ex5: "security" };
    const topic = params.get("topic") || expertTopic[params.get("expert")];
    const select = form.querySelector("#f-topic");
    if (topic && select.querySelector(`option[value="${CSS.escape(topic)}"]`)) select.value = topic;
    const status = form.querySelector(".form__status");
    form.addEventListener("submit", e => {
      e.preventDefault();
      let first = null;
      form.querySelectorAll("[required]").forEach(f => {
        const bad = !f.value.trim() || (f.type === "email" && !f.checkValidity());
        f.setAttribute("aria-invalid", String(bad));
        if (bad && !first) first = f;
      });
      status.classList.toggle("is-error", !!first);
      if (first) { status.textContent = "Fill in your name, a valid work email and your question."; first.focus(); return; }
      status.textContent = "Inquiry sent. We'll reply within one business day.";
      form.reset();
    });
  }

  // On the Netlify preview, Netlify shows a badge in the bottom-right corner; lift the chat button above it.
  if (/\.netlify\.app$/.test(location.hostname)) document.documentElement.classList.add("is-netlify-preview");

  // AI chat: load chat.js on first use only
  const launch = document.querySelector(".chat-launch");
  let chat;
  async function openChat(question) {
    if (!chat) {
      launch.setAttribute("aria-busy", "true");
      chat = await import("./chat.js");
      launch.removeAttribute("aria-busy");
    }
    chat.open(launch, question);
  }
  launch?.addEventListener("click", () => openChat());
  document.querySelectorAll("[data-ask]").forEach(f => f.addEventListener("submit", e => {
    e.preventDefault();
    const q = f.querySelector("input").value.trim();
    openChat(q);
    f.reset();
  }));
})();
