// IQRA School & College Daggar Buner - Main JavaScript

// Mobile menu toggle, dropdown toggle, outside-click-close, and active-link
// highlighting all live in includes/header.php's inline script (it has the
// full, correct logic for all of these) — not duplicated here.

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// In-page anchor links. Smooth scrolling comes from CSS (scroll-behavior,
// switched off for reduced motion) and [id] { scroll-margin-top } keeps the
// target clear of the sticky header. Here we only stop placeholder href="#"
// links from jumping to the top, and move keyboard focus to the target.
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href^="#"]');
    if (!link) return;
    const hash = link.getAttribute('href');
    if (hash === '#') { e.preventDefault(); return; }
    let target = null;
    try { target = document.querySelector(hash); } catch (err) { return; }
    if (!target) return;
    if (!target.hasAttribute('tabindex') && !/^(A|BUTTON|INPUT|SELECT|TEXTAREA)$/.test(target.tagName)) {
        target.setAttribute('tabindex', '-1');
    }
    // let the browser scroll; focus afterwards without a second jump
    setTimeout(function () { target.focus({ preventScroll: true }); }, prefersReducedMotion ? 0 : 400);
});

// Gentle reveal for generic .card blocks (e.g. alumni page)
(function () {
    const cards = document.querySelectorAll('.card');
    if (!cards.length || prefersReducedMotion || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'none';
            io.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    cards.forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(16px)';
        el.style.transition = 'opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out)';
        io.observe(el);
    });
})();

// Forms: inline validation messages + submit loading state.
// Uses the browser's own constraint validation (required, type, min/max …),
// so the rules stay in the HTML and the server-side checks are unchanged.
(function () {
    var FIELD = '.form-group, .adm-field, .ct-field, .elg-field, .fc-field';

    function messageFor(el) {
        var v = el.validity;
        if (v.valueMissing) {
            if (el.type === 'file') return 'Please choose a file.';
            if (el.tagName === 'SELECT') return 'Please select an option.';
            return 'This field is required.';
        }
        if (v.typeMismatch && el.type === 'email') return 'Enter a valid email address, e.g. name@example.com.';
        if (v.typeMismatch && el.type === 'url') return 'Enter a valid web address, e.g. https://example.com.';
        if (v.patternMismatch) return el.title || 'Please use the requested format.';
        if (v.rangeUnderflow) return 'Value must be ' + el.min + ' or more.';
        if (v.rangeOverflow) return 'Value must be ' + el.max + ' or less.';
        if (v.tooShort) return 'Use at least ' + el.minLength + ' characters.';
        if (v.badInput) return 'Please enter a valid number.';
        return el.validationMessage;
    }

    function showError(el) {
        var wrap = el.closest(FIELD) || el.parentElement;
        var id = (el.id || el.name || 'field') + '-error';
        var msg = document.getElementById(id);
        if (!msg) {
            msg = document.createElement('p');
            msg.className = 'field-error';
            msg.id = id;
            msg.setAttribute('aria-live', 'polite');
            wrap.appendChild(msg);
        }
        msg.innerHTML = '<i class="fas fa-circle-exclamation" aria-hidden="true"></i> ';
        msg.appendChild(document.createTextNode(messageFor(el)));
        el.setAttribute('aria-invalid', 'true');
        var described = (el.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
        if (described.indexOf(id) === -1) { described.push(id); el.setAttribute('aria-describedby', described.join(' ')); }
    }

    function clearError(el) {
        if (el.getAttribute('aria-invalid') !== 'true') return;
        el.removeAttribute('aria-invalid');
        var msg = document.getElementById((el.id || el.name || 'field') + '-error');
        if (msg) msg.remove();
    }

    document.querySelectorAll('form').forEach(function (form) {
        var firstInvalid = null;

        // Replace the browser bubble with an inline message; focus the first problem
        form.addEventListener('invalid', function (e) {
            e.preventDefault();
            showError(e.target);
            if (!firstInvalid) {
                firstInvalid = e.target;
                setTimeout(function () {
                    var target = firstInvalid.type === 'file' ? firstInvalid.closest(FIELD + ', .adm-upload') || firstInvalid : firstInvalid;
                    firstInvalid.focus({ preventScroll: true });
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid = null;
                }, 0);
            }
        }, true);

        // Clear a message as soon as the field becomes valid
        ['input', 'change'].forEach(function (type) {
            form.addEventListener(type, function (e) {
                var el = e.target;
                if (el.willValidate && el.checkValidity()) clearError(el);
            });
        });

        // Loading state + double-submit guard. The button is NOT disabled,
        // because disabled submit buttons are left out of the POST data
        // (some handlers check e.g. isset($_POST['submit_alumni'])).
        form.addEventListener('submit', function (e) {
            if (form.dataset.submitting === '1') { e.preventDefault(); return; }
            form.dataset.submitting = '1';
            var btn = e.submitter || form.querySelector('[type="submit"]');
            if (btn) { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); }
        });
    });

    // Reset if the page is restored from the back/forward cache
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
            form.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); });
        });
    });
})();

// Gallery Lightbox
function createLightbox() {
    const galleryImages = document.querySelectorAll('.gallery-item img');

    if (galleryImages.length === 0) return;

    // Create lightbox elements
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img src="" alt="">
            <div class="lightbox-nav">
                <button class="lightbox-prev">&#10094;</button>
                <button class="lightbox-next">&#10095;</button>
            </div>
        </div>
    `;
    document.body.appendChild(lightbox);

    const lightboxImg = lightbox.querySelector('img');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');

    let currentIndex = 0;

    function showImage(index) {
        currentIndex = index;
        lightboxImg.src = galleryImages[index].src;
        lightbox.style.display = 'flex';
    }

    galleryImages.forEach((img, index) => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', () => showImage(index));
    });

    closeBtn.addEventListener('click', () => {
        lightbox.style.display = 'none';
    });

    prevBtn.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        showImage(currentIndex);
    });

    nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % galleryImages.length;
        showImage(currentIndex);
    });

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            lightbox.style.display = 'none';
        }
    });
}

// Initialize lightbox when DOM is ready
document.addEventListener('DOMContentLoaded', createLightbox);

// Add lightbox styles dynamically
const lightboxStyles = `
    .lightbox {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        align-items: center;
        justify-content: center;
    }

    .lightbox-content {
        position: relative;
        max-width: 90%;
        max-height: 90%;
    }

    .lightbox-content img {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
    }

    .lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }

    .lightbox-close:hover {
        color: #0BA6C9;
    }

    .lightbox-nav button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: none;
        padding: 15px 20px;
        font-size: 24px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .lightbox-nav button:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    .lightbox-prev {
        left: -60px;
    }

    .lightbox-next {
        right: -60px;
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = lightboxStyles;
document.head.appendChild(styleSheet);
