(() => {
  const bindPasswordToggle = () => {
    const toggle = document.getElementById("togglePassword");
    const input = document.querySelector(".ardhi-password-input");
    const icon = document.getElementById("togglePasswordIcon");

    if (!toggle || !input || !icon || toggle.dataset.bound === "1") {
      return;
    }

    toggle.dataset.bound = "1";
    toggle.addEventListener("click", () => {
      const showing = input.getAttribute("type") === "text";
      input.setAttribute("type", showing ? "password" : "text");
      icon.classList.toggle("bi-eye", !showing);
      icon.classList.toggle("bi-eye-slash", showing);
      toggle.setAttribute("aria-label", showing ? "Show password" : "Hide password");
    });
  };

  bindPasswordToggle();
  document.addEventListener("livewire:navigated", bindPasswordToggle);
  document.addEventListener("livewire:init", () => {
    Livewire.hook("morph.updated", () => bindPasswordToggle());
  });
})();
