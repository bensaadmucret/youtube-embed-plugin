// admin.js

document.addEventListener("DOMContentLoaded", () => {
  const deleteButtons = document.querySelectorAll('form button[type="submit"]');

  deleteButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      if (btn.innerText.includes("Supprimer")) {
        if (!confirm("⚠️ Es-tu sûr de vouloir supprimer cette chaîne ?")) {
          e.preventDefault();
        }
      }
    });
  });
});
