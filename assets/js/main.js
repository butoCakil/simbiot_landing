/* ================================================================
   SimbIoT — main.js
   1. IoT node network canvas (hero background)
   2. Feedback form AJAX submit
   3. Approved feedback loader
================================================================ */

'use strict';

// ----------------------------------------------------------------
// 1. IoT Node Network Canvas
// ----------------------------------------------------------------

class IoTCanvas {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;

    this.ctx   = this.canvas.getContext('2d');
    this.nodes = [];
    this.mouse = { x: null, y: null };
    this.N     = 60;     // jumlah node
    this.DIST  = 130;    // jarak maks koneksi antar node

    this._resize = this._resize.bind(this);
    this._loop   = this._loop.bind(this);
    this._onMove = this._onMove.bind(this);
    this._onLeave = () => { this.mouse.x = null; this.mouse.y = null; };

    this._resize();
    this._spawnNodes();

    window.addEventListener('resize', this._resize);
    this.canvas.addEventListener('mousemove', this._onMove);
    this.canvas.addEventListener('mouseleave', this._onLeave);

    this._loop();
  }

  _resize() {
    // Hanya update dimensi, jangan spawn ulang (node akan menyesuaikan)
    this.canvas.width  = this.canvas.offsetWidth;
    this.canvas.height = this.canvas.offsetHeight;
  }

  _spawnNodes() {
    this.nodes = Array.from({ length: this.N }, () => ({
      x:  Math.random() * this.canvas.width,
      y:  Math.random() * this.canvas.height,
      vx: (Math.random() - 0.5) * 0.3,
      vy: (Math.random() - 0.5) * 0.3,
      r:  Math.random() * 1.5 + 0.8,
    }));
  }

  _onMove(e) {
    const rect     = this.canvas.getBoundingClientRect();
    this.mouse.x   = e.clientX - rect.left;
    this.mouse.y   = e.clientY - rect.top;
  }

  _loop() {
    const { ctx, canvas, nodes, DIST } = this;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Move nodes
    for (const n of nodes) {
      n.x += n.vx;
      n.y += n.vy;
      if (n.x < 0 || n.x > canvas.width)  n.vx *= -1;
      if (n.y < 0 || n.y > canvas.height) n.vy *= -1;
    }

    // Draw connections
    for (let i = 0; i < nodes.length; i++) {
      for (let j = i + 1; j < nodes.length; j++) {
        const dx   = nodes[i].x - nodes[j].x;
        const dy   = nodes[i].y - nodes[j].y;
        const dist = Math.hypot(dx, dy);
        if (dist < DIST) {
          const alpha = (1 - dist / DIST) * 0.22;
          ctx.beginPath();
          ctx.moveTo(nodes[i].x, nodes[i].y);
          ctx.lineTo(nodes[j].x, nodes[j].y);
          ctx.strokeStyle = `rgba(13,148,136,${alpha})`;
          ctx.lineWidth   = 0.7;
          ctx.stroke();
        }
      }
    }

    // Draw nodes (with mouse proximity glow)
    for (const n of nodes) {
      let glow = 0;
      if (this.mouse.x !== null) {
        const d = Math.hypot(n.x - this.mouse.x, n.y - this.mouse.y);
        if (d < 110) glow = 1 - d / 110;
      }

      ctx.beginPath();
      ctx.arc(n.x, n.y, n.r + glow * 2.5, 0, Math.PI * 2);
      ctx.fillStyle = glow > 0.01
        ? `rgba(56,189,248,${0.4 + glow * 0.55})`
        : 'rgba(56,189,248,0.38)';
      ctx.fill();
    }

    requestAnimationFrame(this._loop);
  }
}

// ----------------------------------------------------------------
// 2. Feedback Form — AJAX
// ----------------------------------------------------------------

function initFeedbackForm() {
  const form   = document.getElementById('feedback-form');
  const status = document.getElementById('fb-status');
  const submit = document.getElementById('fb-submit');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validasi minimal sisi client
    const name    = form.querySelector('[name=name]').value.trim();
    const role    = form.querySelector('[name=role]').value;
    const message = form.querySelector('[name=message]').value.trim();

    if (name.length < 2 || !role || message.length < 10) {
      showStatus('error', 'Lengkapi semua field dengan benar (nama ≥2 karakter, pesan ≥10 karakter).');
      return;
    }

    setLoading(true);
    hideStatus();

    try {
      const res  = await fetch('/submit_feedback.php', {
        method: 'POST',
        body: new FormData(form),
      });
      const data = await res.json();

      if (data.success) {
        showStatus('success', data.message);
        form.reset();
      } else {
        showStatus('error', data.message || 'Terjadi kesalahan.');
      }
    } catch {
      showStatus('error', 'Koneksi bermasalah. Silakan coba beberapa saat lagi.');
    } finally {
      setLoading(false);
    }
  });

  function setLoading(isLoading) {
    submit.disabled    = isLoading;
    submit.textContent = isLoading ? 'Mengirim…' : 'Kirim Masukan';
  }

  function showStatus(type, message) {
    status.textContent = message;
    status.className   = `fb-status ${type}`;
    status.hidden      = false;
  }

  function hideStatus() {
    status.hidden = true;
  }
}

// ----------------------------------------------------------------
// 3. Load Approved Feedback
// ----------------------------------------------------------------

async function loadFeedback() {
  const container = document.getElementById('feedback-list');
  if (!container) return;

  try {
    const res  = await fetch('/api/feedback.php');
    const data = await res.json();

    if (!data.success || !data.data || data.data.length === 0) {
      container.innerHTML = `
        <div class="fb-empty">
          Belum ada komentar yang ditampilkan.<br>
          Jadilah yang pertama menulis masukan!
        </div>`;
      return;
    }

    container.innerHTML = data.data.map(renderCard).join('');
  } catch {
    container.innerHTML = `<div class="fb-empty">Gagal memuat komentar.</div>`;
  }
}

function renderCard(fb) {
  const responseHtml = fb.response
    ? `<div class="fb-public-response">
        <div class="fb-public-response-label">Tanggapan</div>
        <div class="fb-public-response-text">${esc(fb.response).replace(/\n/g, '<br>')}</div>
       </div>`
    : '';

  return `
    <div class="fb-public-card" role="listitem">
      <div class="fb-public-header">
        <div>
          <div class="fb-public-name">${esc(fb.name)}</div>
          <span class="fb-public-role">${esc(fb.role)}</span>
        </div>
        <div class="fb-public-date">${esc(fb.date)}</div>
      </div>
      <div class="fb-public-message">${esc(fb.message).replace(/\n/g, '<br>')}</div>
      ${responseHtml}
    </div>`;
}

// Escape HTML untuk output dari JSON
function esc(str) {
  return String(str ?? '')
    .replace(/&/g,  '&amp;')
    .replace(/</g,  '&lt;')
    .replace(/>/g,  '&gt;')
    .replace(/"/g,  '&quot;')
    .replace(/'/g,  '&#39;');
}

// ----------------------------------------------------------------
// Navbar — scroll behavior & active link
// ----------------------------------------------------------------
function initNavbar() {
  const navbar   = document.getElementById('navbar');
  const links    = document.querySelectorAll('.nav-link');
  const toggle   = document.getElementById('navbar-toggle');
  const menu     = document.getElementById('navbar-links');
  const sections = ['home', 'tentang', 'platform', 'feedback'];

  toggle?.addEventListener('click', () => menu.classList.toggle('open'));
  links.forEach(l => l.addEventListener('click', () => menu.classList.remove('open')));

  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 20);

    let current = '';
    for (const id of sections) {
      const el = document.getElementById(id);
      if (el && el.getBoundingClientRect().top <= 80) current = id;
    }
    links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === `#${current}`));
  }, { passive: true });
}

// ----------------------------------------------------------------
// Back to Top
// ----------------------------------------------------------------
function initBackToTop() {
  const btn = document.getElementById('back-to-top');
  if (!btn) return;

  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 300);
  }, { passive: true });

  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ----------------------------------------------------------------
// Init
// ----------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  new IoTCanvas('iot-canvas');
  initNavbar();
  initBackToTop();
  initFeedbackForm();
  loadFeedback();
  checkStatus();
  setInterval(checkStatus, 60000);
});

// ----------------------------------------------------------------
// 4. Status checker — update badge & klik kartu
// ----------------------------------------------------------------
async function checkStatus() {
  try {
    const res  = await fetch('/api/status.php');
    const data = await res.json();
    if (!data.success) return;

    const map = {
      'adlean':    { card: 'card-adlean',    badge: 'badge-adlean' },
      'kitacatat': { card: 'card-kitacatat', badge: 'badge-kitacatat' },
      'broker':    { card: 'card-broker',    badge: 'badge-broker' },
      'panel':     { card: 'card-panel',     badge: 'badge-panel' },
      'ben':       { card: 'card-ben',       badge: 'badge-ben' },
      'simbion':   { card: 'card-simbion',   badge: 'badge-simbion' },
    };

    for (const [key, ids] of Object.entries(map)) {
      const active = data.status[key];
      const card   = document.getElementById(ids.card);
      const badge  = document.getElementById(ids.badge);
      if (!card || !badge) continue;

      if (active) {
        badge.textContent      = 'Aktif';
        badge.className        = 'app-badge badge-active';
        card.style.pointerEvents = '';
        card.style.opacity     = '';
      } else {
        badge.textContent      = 'Tidak Aktif';
        badge.className        = 'app-badge badge-offline';
        card.style.pointerEvents = 'none';
        card.style.opacity     = '0.5';
      }
    }
  } catch { /* silent fail */ }
}