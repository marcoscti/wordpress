(() => {
  "use strict";

  const initialsFor = (name) => String(name || "?").trim().charAt(0).toUpperCase();

  const wrapText = (context, value, maxWidth) => {
    const words = String(value || "").split(/\s+/);
    const lines = [];
    let line = "";

    words.forEach((word) => {
      const candidate = line ? `${line} ${word}` : word;
      if (context.measureText(word).width > maxWidth) {
        if (line) {
          lines.push(line);
          line = "";
        }
        let segment = "";
        Array.from(word).forEach((character) => {
          if (segment && context.measureText(segment + character).width > maxWidth) {
            lines.push(segment);
            segment = character;
          } else {
            segment += character;
          }
        });
        line = segment;
      } else if (line && context.measureText(candidate).width > maxWidth) {
        lines.push(line);
        line = word;
      } else {
        line = candidate;
      }
    });

    if (line) lines.push(line);
    return lines;
  };

  const drawWrappedLines = (context, lines, x, y, lineHeight) => {
    lines.forEach((line, index) => context.fillText(line, x, y + index * lineHeight));
  };

  const drawJustifiedLines = (context, lines, x, y, maxWidth, lineHeight) => {
    context.textAlign = "left";
    lines.forEach((line, index) => {
      const words = line.split(/\s+/);
      const isLastLine = index === lines.length - 1;
      if (isLastLine || words.length < 2) {
        context.fillText(line, x, y + index * lineHeight);
        return;
      }

      const wordsWidth = words.reduce(
        (total, word) => total + context.measureText(word).width,
        0,
      );
      const gap = (maxWidth - wordsWidth) / (words.length - 1);
      let wordX = x;
      words.forEach((word) => {
        context.fillText(word, wordX, y + index * lineHeight);
        wordX += context.measureText(word).width + gap;
      });
    });
    context.textAlign = "center";
  };

  const loadImage = (url) => new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = () => reject(new Error("Não foi possível carregar a foto para gerar o cartão."));
    image.src = url;
  });

  const downloadCard = async (participant, position) => {
    const canvas = document.createElement("canvas");
    canvas.width = 1080;
    const context = canvas.getContext("2d");
    if (!context) throw new Error("Não foi possível preparar a imagem do cartão.");

    const category = participant.category || {};
    context.font = "800 62px Arial, sans-serif";
    const nameLines = wrapText(context, participant.name || "Participante", 820);
    context.font = "700 44px Arial, sans-serif";
    const categoryLabel = `${category.emoji || "🏆"} ${category.name || "Participante"}`;
    const categoryLines = wrapText(context, categoryLabel, 820);
    context.font = "500 36px Arial, sans-serif";
    const unitLines = wrapText(context, participant.unit || "Unidade não informada", 820);
    context.font = "500 30px Arial, sans-serif";
    const descriptionLines = wrapText(context, category.description || "", 800);

    const nameY = 695;
    const categoryY = nameY + nameLines.length * 72 + 34;
    const unitY = categoryY + categoryLines.length * 54 + 20;
    const scoreY = unitY + unitLines.length * 44 + 70;
    const descriptionY = scoreY + 65;
    canvas.height = Math.max(1350, descriptionY + Math.max(0, descriptionLines.length - 1) * 40 + 165);

    const background = context.createLinearGradient(0, 0, canvas.width, canvas.height);
    background.addColorStop(0, "#fff9df");
    background.addColorStop(1, "#eaf7fa");
    context.fillStyle = background;
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.fillStyle = "#ffffff";
    context.beginPath();
    context.moveTo(122, 65);
    context.arcTo(1010, 65, 1010, 117, 52);
    context.arcTo(1010, canvas.height - 65, 958, canvas.height - 65, 52);
    context.arcTo(70, canvas.height - 65, 70, canvas.height - 117, 52);
    context.arcTo(70, 65, 122, 65, 52);
    context.closePath();
    context.fill();
    context.strokeStyle = "#e6ebf3";
    context.lineWidth = 3;
    context.stroke();

    context.textAlign = "center";
    context.fillStyle = "#0094c6";
    context.font = "800 34px Arial, sans-serif";
    context.fillText("RANKING • DIA DAS CRIANÇAS", 540, 135);

    const medal = position <= 3 ? ["🥇", "🥈", "🥉"][position - 1] : `${position}º`;
    context.font = "700 48px Arial, sans-serif";
    context.fillStyle = "#172033";
    context.fillText(`${medal} LUGAR`, 540, 205);

    if (participant.photo_url) {
      const image = await loadImage(participant.photo_url);
      const size = 360;
      const y = 255;
      context.save();
      context.beginPath();
      context.arc(540, y + size / 2, size / 2, 0, Math.PI * 2);
      context.clip();
      const scale = Math.max(size / image.width, size / image.height);
      const width = image.width * scale;
      const height = image.height * scale;
      context.drawImage(image, 540 - width / 2, y + size / 2 - height / 2, width, height);
      context.restore();
      context.strokeStyle = "#ffffff";
      context.lineWidth = 12;
      context.beginPath();
      context.arc(540, y + size / 2, size / 2, 0, Math.PI * 2);
      context.stroke();
    } else {
      context.fillStyle = "#ffe66b";
      context.beginPath();
      context.arc(540, 435, 180, 0, Math.PI * 2);
      context.fill();
      context.fillStyle = "#172033";
      context.font = "800 150px Arial, sans-serif";
      context.fillText(initialsFor(participant.name), 540, 490);
    }

    context.fillStyle = "#172033";
    context.font = "800 62px Arial, sans-serif";
    drawWrappedLines(context, nameLines, 540, nameY, 72);

    context.fillStyle = "#0094c6";
    context.font = "700 44px Arial, sans-serif";
    drawWrappedLines(context, categoryLines, 540, categoryY, 54);

    context.fillStyle = "#5d687c";
    context.font = "500 36px Arial, sans-serif";
    drawWrappedLines(context, unitLines, 540, unitY, 44);

    context.fillStyle = "#172033";
    context.font = "800 50px Arial, sans-serif";
    context.fillText(`${Number(participant.score) || 0} pontos`, 540, scoreY);

    context.fillStyle = "#5d687c";
    context.font = "500 30px Arial, sans-serif";
    drawJustifiedLines(context, descriptionLines, 140, descriptionY, 800, 40);

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/png"));
    if (!blob) throw new Error("Não foi possível gerar a imagem do cartão.");

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `cartao-ranking-${String(participant.name || "participante").trim().replace(/[^a-z0-9]+/gi, "-").toLowerCase()}.png`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  };

  window.DCDC_Card = { downloadCard };
})();
