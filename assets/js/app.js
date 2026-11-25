/* =========================================
   SCRIPTS GENERALES - ARTESANÍAS WAYÚU
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Ocultar automáticamente alertas de éxito tras 4.5 segundos
    const alertasExito = document.querySelectorAll(".alerta-exito");
    alertasExito.forEach(function (alerta) {
        setTimeout(function () {
            alerta.style.opacity = "0";
            setTimeout(function () {
                alerta.style.display = "none";
            }, 400);
        }, 4500);
    });

    // 2. Inicializar gráficas si estamos en reportes.php
    if (typeof window.datosReportes !== "undefined") {
        dibujarGraficasReportes(window.datosReportes);
    }
});

// Funciones para filas dinámicas en agregar_pedido.php
function agregarFila() {
    const tbody = document.getElementById("contenedor-filas");
    if (!tbody) return;
    const primeraFila = tbody.querySelector(".fila-item");
    const nuevaFila = primeraFila.cloneNode(true);
    nuevaFila.querySelector("select").value = "";
    nuevaFila.querySelector("input").value = 1;
    tbody.appendChild(nuevaFila);
}

function eliminarFila(boton) {
    const tbody = document.getElementById("contenedor-filas");
    if (!tbody) return;
    if (tbody.querySelectorAll(".fila-item").length > 1) {
        boton.closest("tr").remove();
    } else {
        alert("El pedido debe tener al menos un producto.");
    }
}

// Renderizado nativo de gráficas en HTML5 Canvas
function dibujarGraficasReportes(datos) {
    const paletaWayuu = ["#753b24", "#d9822b", "#287a45", "#c0392b", "#2980b9", "#8e44ad"];

    function prepararCanvas(id) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        const rect = canvas.parentElement.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        const ctx = canvas.getContext("2d");
        ctx.scale(dpr, dpr);
        return { ctx, w: rect.width, h: rect.height };
    }

    // Gráfica 1: Dona de Métodos de Pago
    if (datos.labelsMetodo && datos.labelsMetodo.length > 0) {
        const c = prepararCanvas("graficaMetodos");
        if (c) {
            const { ctx, w, h } = c;
            const total = datos.datosMetodo.reduce((a, b) => a + b, 0);
            const cx = w * 0.35, cy = h * 0.5, r = Math.min(cx, cy) - 18;
            let anguloInicio = -Math.PI / 2;

            datos.datosMetodo.forEach((val, i) => {
                const porcion = (val / total) * Math.PI * 2;
                ctx.beginPath();
                ctx.moveTo(cx, cy);
                ctx.arc(cx, cy, r, anguloInicio, anguloInicio + porcion);
                ctx.closePath();
                ctx.fillStyle = paletaWayuu[i % paletaWayuu.length];
                ctx.fill();
                ctx.strokeStyle = "#fff";
                ctx.lineWidth = 2;
                ctx.stroke();
                anguloInicio += porcion;
            });

            ctx.beginPath();
            ctx.arc(cx, cy, r * 0.52, 0, Math.PI * 2);
            ctx.fillStyle = "#fff";
            ctx.fill();

            ctx.font = "bold 12px Arial";
            ctx.textAlign = "left";
            datos.labelsMetodo.forEach((lbl, i) => {
                const y = 35 + i * 28;
                const pct = Math.round((datos.datosMetodo[i] / total) * 100);
                ctx.fillStyle = paletaWayuu[i % paletaWayuu.length];
                ctx.fillRect(w * 0.68, y - 10, 12, 12);
                ctx.fillStyle = "#333";
                ctx.fillText(`${lbl} (${pct}%)`, w * 0.68 + 18, y);
            });
        }
    }

    // Gráfica 2: Barras Verticales de Productos
    if (datos.labelsProd && datos.labelsProd.length > 0) {
        const c = prepararCanvas("graficaProductos");
        if (c) {
            const { ctx, w, h } = c;
            const maxVal = Math.max(...datos.datosProd, 1);
            const padL = 35, padR = 15, padT = 25, padB = 45;
            const areaW = w - padL - padR;
            const areaH = h - padT - padB;
            const anchoBarra = Math.min(45, (areaW / datos.datosProd.length) * 0.6);
            const paso = areaW / datos.datosProd.length;

            ctx.strokeStyle = "#ccc";
            ctx.beginPath();
            ctx.moveTo(padL, padT + areaH);
            ctx.lineTo(w - padR, padT + areaH);
            ctx.stroke();

            datos.datosProd.forEach((val, i) => {
                const altoBarra = (val / maxVal) * areaH;
                const x = padL + i * paso + (paso - anchoBarra) / 2;
                const y = padT + areaH - altoBarra;

                ctx.fillStyle = "#d9822b";
                ctx.fillRect(x, y, anchoBarra, altoBarra);

                ctx.fillStyle = "#333";
                ctx.font = "bold 12px Arial";
                ctx.textAlign = "center";
                ctx.fillText(val, x + anchoBarra / 2, y - 6);

                ctx.font = "11px Arial";
                const nombreCorto = datos.labelsProd[i].length > 12 ? datos.labelsProd[i].slice(0, 11) + "…" : datos.labelsProd[i];
                ctx.fillText(nombreCorto, x + anchoBarra / 2, padT + areaH + 18);
            });
        }
    }

    // Gráfica 3: Barras Horizontales de Artesanos
    if (datos.labelsArt && datos.labelsArt.length > 0) {
        const c = prepararCanvas("graficaArtesanos");
        if (c) {
            const { ctx, w, h } = c;
            const maxVal = Math.max(...datos.datosArt, 1);
            const padL = 105, padR = 65, padT = 15, padB = 20;
            const areaW = w - padL - padR;
            const areaH = h - padT - padB;
            const altoBarra = Math.min(28, (areaH / datos.datosArt.length) * 0.6);
            const paso = areaH / datos.datosArt.length;

            datos.datosArt.forEach((val, i) => {
                const anchoBarra = (val / maxVal) * areaW;
                const y = padT + i * paso + (paso - altoBarra) / 2;

                ctx.fillStyle = "#753b24";
                ctx.fillRect(padL, y, anchoBarra, altoBarra);

                ctx.fillStyle = "#333";
                ctx.font = "bold 11px Arial";
                ctx.textAlign = "right";
                const nombreCorto = datos.labelsArt[i].length > 14 ? datos.labelsArt[i].slice(0, 13) + "…" : datos.labelsArt[i];
                ctx.fillText(nombreCorto, padL - 8, y + altoBarra / 2 + 4);

                ctx.textAlign = "left";
                ctx.fillText("$" + val.toLocaleString("es-CO"), padL + anchoBarra + 6, y + altoBarra / 2 + 4);
            });
        }
    }
}
