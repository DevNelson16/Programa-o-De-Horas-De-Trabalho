/**
 * ============================================================
 * JavaScript Principal - Interatividade Global (CORRIGIDO)
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {
  // ========================================================
  // Sidebar Toggle (Desktop e Mobile)
  // ========================================================
  const sidebar = document.getElementById("sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const menuToggle = document.getElementById("menuToggle");
  const overlay = document.getElementById("sidebarOverlay");

  if (sidebar) {
    // MOBILE RESET
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("collapsed");
      localStorage.removeItem("sidebarCollapsed");
    }

    // Restaurar estado no desktop
    if (window.innerWidth > 768) {
      const savedState = localStorage.getItem("sidebarCollapsed");

      if (savedState === "true") {
        sidebar.classList.add("collapsed");
      }
    }

    // Toggle desktop
    if (sidebarToggle) {
      sidebarToggle.addEventListener("click", function () {
        if (window.innerWidth > 768) {
          sidebar.classList.toggle("collapsed");

          const collapsed = sidebar.classList.contains("collapsed");
          localStorage.setItem("sidebarCollapsed", collapsed);
        }
      });
    }

    // Toggle mobile
    if (menuToggle) {
      menuToggle.addEventListener("click", function () {
        sidebar.classList.remove("collapsed");
        sidebar.classList.add("active");

        if (overlay) {
          overlay.classList.add("active");
        }
      });
    }

    // Fechar mobile
    if (overlay) {
      overlay.addEventListener("click", function () {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
      });
    }
  }

  // ========================================================
  // Flash Messages - Auto fechar
  // ========================================================
  const flashMessage = document.getElementById("flashMessage");

  if (flashMessage) {
    setTimeout(function () {
      flashMessage.style.opacity = "0";
      flashMessage.style.transform = "translateX(100%)";
      flashMessage.style.transition = "all 0.3s ease";

      setTimeout(function () {
        flashMessage.remove();
      }, 300);
    }, 5000);
  }

  // ========================================================
  // Confirmar antes de apagar
  // ========================================================
  document.querySelectorAll(".confirm-delete").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      if (
        !confirm(
          "Tem certeza que deseja apagar este registo? Esta acao nao pode ser desfeita.",
        )
      ) {
        e.preventDefault();
      }
    });
  });

  // ========================================================
  // Animacao entrada
  // ========================================================
  const fadeElements = document.querySelectorAll(
    ".card, .stat-card, .relatorio-card",
  );

  fadeElements.forEach(function (el, index) {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";

    setTimeout(function () {
      el.style.transition = "all 0.4s ease";
      el.style.opacity = "1";
      el.style.transform = "translateY(0)";
    }, index * 80);
  });

  // ========================================================
  // Scroll tables
  // ========================================================
  const tableContainers = document.querySelectorAll(".table-container");

  tableContainers.forEach(function (container) {
    if (container.scrollWidth > container.clientWidth) {
      container.style.position = "relative";
      container.style.cursor = "grab";
    }
  });
});

/**
 * Abre modal
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);

  if (modal) {
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  }
}

/**
 * Fecha modal
 */
function closeModal(modalId) {
  const modal = document.getElementById(modalId);

  if (modal) {
    modal.classList.remove("active");
    document.body.style.overflow = "";
  }
}

/**
 * Formatar tempo
 */
function formatarTempo(totalSegundos) {
  const horas = Math.floor(totalSegundos / 3600);
  const minutos = Math.floor((totalSegundos % 3600) / 60);
  const segundos = totalSegundos % 60;

  return `${String(horas).padStart(2, "0")}:${String(minutos).padStart(2, "0")}:${String(segundos).padStart(2, "0")}`;
}

/**
 * Toast
 */
function showToast(mensagem, tipo = "success") {
  const toast = document.createElement("div");

  toast.className = `flash-message flash-${tipo}`;
  toast.style.cssText =
    "position:fixed;top:20px;right:20px;z-index:99999;animation:slideIn 0.3s ease;";

  const iconMap = {
    success: "check-circle",
    error: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  };

  toast.innerHTML = `
        <i class="fas fa-${iconMap[tipo]}"></i>
        <span>${mensagem}</span>
        <button onclick="this.parentElement.remove()" class="flash-close">
            <i class="fas fa-times"></i>
        </button>
    `;

  document.body.appendChild(toast);

  setTimeout(function () {
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100%)";
    toast.style.transition = "all 0.3s ease";

    setTimeout(function () {
      toast.remove();
    }, 300);
  }, 4000);
}
