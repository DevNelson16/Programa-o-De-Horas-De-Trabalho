/**
 * ============================================================
 * Temporizador de Programacao - VERSAO CORRIGIDA
 * ============================================================
 */

class Temporizador {
  constructor() {
    // Elementos do DOM
    this.display = document.getElementById("timerDisplay");
    this.statusEl = document.getElementById("timerStatus");
    this.btnIniciar = document.getElementById("btnIniciar");
    this.btnPausar = document.getElementById("btnPausar");
    this.btnParar = document.getElementById("btnParar");
    this.descInput = document.getElementById("timerDescricao");

    // Estado
    this.segundosTotais = 0;
    this.estaRodando = false;
    this.estaPausado = false;
    this.intervalId = null;
    this.registoId = null;

    this.inicializar();
  }

  inicializar() {
    this.verificarTemporizadorAtivo();

    this.btnIniciar?.addEventListener("click", () => this.iniciar());
    this.btnPausar?.addEventListener("click", () => this.pausar());
    this.btnParar?.addEventListener("click", () => this.parar());

    this.atualizarDisplay();
  }

  /**
   * 🔥 IMPORTANTE: caminho absoluto corrigido
   */
  async fetchAPI(url, options = {}) {
    try {
      const response = await fetch(
        `/programacao-horas/public/api/temporizador.php${url}`,
        options,
      );

      const text = await response.text();
      console.log("API RAW:", text); // debug

      return JSON.parse(text);
    } catch (error) {
      console.error("Erro API:", error);
      showToast("Erro de comunicação com servidor", "error");
      return null;
    }
  }

  async verificarTemporizadorAtivo() {
    const data = await this.fetchAPI("?acao=status");

    if (data && data.ativo && data.registo) {
      this.registoId = data.registo.id;

      const inicio = new Date(
        data.registo.data + "T" + data.registo.hora_inicio,
      );
      const agora = new Date();

      this.segundosTotais = Math.floor((agora - inicio) / 1000);

      if (this.descInput && data.registo.descricao) {
        this.descInput.value = data.registo.descricao;
      }

      this.estaRodando = true;

      this.atualizarEstado("running");
      this.iniciarContagem();
    }
  }

  async iniciar() {
    if (this.estaRodando) return;

    const descricao = this.descInput ? this.descInput.value.trim() : "";

    const data = await this.fetchAPI("?acao=iniciar", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ descricao }),
    });

    if (data && data.sucesso) {
      this.registoId = data.registo_id;
      this.segundosTotais = 0;
      this.estaRodando = true;
      this.estaPausado = false;

      this.atualizarEstado("running");
      this.iniciarContagem();

      showToast("Temporizador iniciado!", "success");
    } else {
      showToast(data?.mensagem || "Erro ao iniciar", "error");
    }
  }

  async pausar() {
    if (!this.estaRodando || this.estaPausado) return;

    const data = await this.fetchAPI("?acao=pausar", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ registo_id: this.registoId }),
    });

    if (data && data.sucesso) {
      this.estaPausado = true;
      this.pararContagem();
      this.atualizarEstado("paused");
      showToast("Pausado", "warning");
    }
  }

  async parar() {
    if (!this.estaRodando) return;

    const descricao = this.descInput ? this.descInput.value.trim() : "";

    const data = await this.fetchAPI("?acao=parar", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        registo_id: this.registoId,
        descricao,
      }),
    });

    if (data && data.sucesso) {
      this.pararContagem();

      const tempo = formatarTempo(this.segundosTotais);

      this.estaRodando = false;
      this.estaPausado = false;
      this.registoId = null;
      this.segundosTotais = 0;

      if (this.descInput) this.descInput.value = "";

      this.atualizarEstado("idle");
      this.atualizarDisplay();

      showToast(`Guardado! ${tempo}`, "success");
    }
  }

  iniciarContagem() {
    this.pararContagem();

    this.intervalId = setInterval(() => {
      if (!this.estaPausado) {
        this.segundosTotais++;
        this.atualizarDisplay();
      }
    }, 1000);
  }

  pararContagem() {
    clearInterval(this.intervalId);
    this.intervalId = null;
  }

  atualizarDisplay() {
    if (this.display) {
      this.display.textContent = formatarTempo(this.segundosTotais);
    }
  }

  atualizarEstado(estado) {
    if (!this.statusEl) return;

    this.statusEl.className = "timer-status " + estado;

    if (estado === "running") {
      this.statusEl.innerHTML = "⏱ Em andamento";
      this.btnIniciar.disabled = true;
      this.btnPausar.disabled = false;
      this.btnParar.disabled = false;
    }

    if (estado === "paused") {
      this.statusEl.innerHTML = "⏸ Pausado";
      this.btnIniciar.disabled = false;
      this.btnPausar.disabled = true;
      this.btnParar.disabled = false;
    }

    if (estado === "idle") {
      this.statusEl.innerHTML = "Pronto para começar";
      this.btnIniciar.disabled = false;
      this.btnPausar.disabled = true;
      this.btnParar.disabled = true;
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  if (document.getElementById("timerDisplay")) {
    window.temporizador = new Temporizador();
  }
});
