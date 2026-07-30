(() => {
  const panel = document.getElementById("ardhiAuthPanel");
  if (panel) {
    requestAnimationFrame(() => {
      panel.classList.add("is-visible");
    });
  }

  const toggle = document.getElementById("togglePassword");
  const input = document.querySelector(".ardhi-password-input");
  const icon = document.getElementById("togglePasswordIcon");

  if (!toggle || !input || !icon) {
    return;
  }

  toggle.addEventListener("click", () => {
    const showing = input.getAttribute("type") === "text";
    input.setAttribute("type", showing ? "password" : "text");
    icon.classList.toggle("bi-eye", !showing);
    icon.classList.toggle("bi-eye-slash", showing);
    toggle.setAttribute("aria-label", showing ? "Show password" : "Hide password");
  });
})();
