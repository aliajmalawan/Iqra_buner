<?php if (empty(getLeadershipMessages())) return; // nothing published: hide the whole section ?>
<!-- ===================================================
     MODERN LEADERSHIP SECTION - Animated & Unique
     =================================================== -->

<style>
/* === LEADERSHIP — white card: navy portrait panel + message === */
.lm-section { padding: var(--section-y) 0; background: var(--background-color); }

.lm-header { text-align: center; margin-bottom: 44px; }
.lm-header h2 {
    font-size: var(--fs-h2); font-weight: 700; line-height: 1.25; letter-spacing: -0.3px;
    color: var(--primary-color); margin: 0 0 12px;
}
.lm-header h2 span { color: var(--accent-hover); }
.lm-header p { color: var(--text-light); font-size: var(--fs-body); line-height: 1.7; max-width: 620px; margin: 0 auto; }

.lm-cards { display: flex; flex-direction: column; gap: 28px; }

.lm-card {
    display: grid;
    grid-template-columns: 300px 1fr;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    overflow: hidden;
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 0.6s ease, transform 0.6s ease, box-shadow var(--transition);
}
.lm-card.lm-visible { opacity: 1; transform: none; }
.lm-card:hover { box-shadow: var(--shadow-card-hover); }

.lm-card.lm-reverse { grid-template-columns: 1fr 300px; }
.lm-card.lm-reverse .lm-image-side { order: 2; }
.lm-card.lm-reverse .lm-quote-side { order: 1; }

/* Portrait panel */
.lm-image-side {
    background: var(--primary-color);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 40px 28px;
    text-align: center;
}
.lm-img-wrap { margin-bottom: 24px; }
.lm-photo,
.lm-photo-placeholder {
    width: 156px; height: 156px;
    border-radius: 50%;
    outline: 2px solid var(--gold-color);
    outline-offset: 5px;
}
.lm-photo { object-fit: cover; object-position: center 20%; display: block; }
.lm-photo-placeholder {
    background: rgba(255,255,255,0.08);
    display: flex; align-items: center; justify-content: center;
    font-size: 56px; color: rgba(255,255,255,0.5);
}
.lm-role-badge {
    display: inline-block;
    color: var(--accent-bright);
    font-size: 11.5px; font-weight: 600;
    letter-spacing: 1.8px; text-transform: uppercase;
    margin-bottom: 8px;
}
.lm-person-name { font-size: 20px; font-weight: 600; color: #fff; line-height: 1.3; margin: 0 0 4px; }
.lm-person-desig { font-size: 14px; color: rgba(255,255,255,0.68); margin: 0; }

/* Message */
.lm-quote-side {
    padding: 44px 48px;
    display: flex; flex-direction: column; justify-content: center;
}
.lm-quote-icon { font-size: 24px; color: var(--accent-color); margin-bottom: 18px; line-height: 1; }
.lm-message-text { font-size: 15.5px; line-height: 1.85; color: var(--text-color); flex: 1; }
.lm-card-footer {
    display: flex; align-items: center; gap: 18px;
    margin-top: 28px; padding-top: 22px;
    border-top: 1px solid var(--border-color);
}
.lm-sig-img { height: 40px; width: auto; max-width: 130px; opacity: 0.8; filter: saturate(0); }
.lm-sig-sep { width: 1px; height: 36px; background: var(--border-color); }
.lm-footer-name { display: block; font-size: 15px; font-weight: 600; color: var(--primary-color); line-height: 1.3; margin-bottom: 2px; }
.lm-footer-role { font-size: 13px; color: var(--text-light); }

.lm-empty {
    text-align: center; padding: 56px 24px;
    color: var(--text-light); background: #fff;
    border: 1px dashed var(--border-color); border-radius: var(--radius-card);
}

@media (max-width: 960px) {
    .lm-card, .lm-card.lm-reverse { grid-template-columns: 1fr; }
    .lm-card.lm-reverse .lm-image-side { order: 1; }
    .lm-card.lm-reverse .lm-quote-side { order: 2; }
    .lm-image-side { padding: 36px 24px; }
    .lm-quote-side { padding: 32px 28px; }
}
@media (max-width: 600px) {
    .lm-section { padding: var(--section-y) 0; }
    .lm-header h2 { font-size: var(--fs-h2); }
    .lm-photo, .lm-photo-placeholder { width: 128px; height: 128px; }
    .lm-quote-side { padding: 28px 22px; }
    .lm-message-text { font-size: 15px; }
}
@media (prefers-reduced-motion: reduce) {
    .lm-card { opacity: 1; transform: none; transition: none; }
}
</style>

<section class="lm-section">
    <div class="container">

        <!-- Section Header -->
        <?php $leadership_header = getLeadershipHeader(); ?>
        <div class="lm-header">
            <span class="section-badge"><?php echo htmlspecialchars($leadership_header['badge']); ?></span>
            <h2><?php echo renderHighlightedTitle($leadership_header['title'], $leadership_header['highlight'], 'span'); ?></h2>
            <p><?php echo htmlspecialchars($leadership_header['subtitle']); ?></p>
        </div>

        <!-- Cards -->
        <div class="lm-cards">
            <?php
            $leaders = getLeadershipMessages();
            if (!empty($leaders)):
                $count = 0;
                foreach ($leaders as $leader):
                    $count++;
                    $is_reverse = ($count % 2 === 0);
            ?>

            <!-- Leader Card -->
            <div class="lm-card <?php echo $is_reverse ? 'lm-reverse' : ''; ?> lm-animate-card">

                <!-- IMAGE SIDE -->
                <div class="lm-image-side">
                    <div class="lm-img-wrap">
                        <?php if (!empty($leader['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($leader['photo']); ?>"
                                 alt="<?php echo htmlspecialchars($leader['name']); ?>"
                                 class="lm-photo"
                                 loading="lazy"
                                 onerror="this.parentElement.innerHTML='<div class=\'lm-photo-placeholder\'><i class=\'fas fa-user-tie\'></i></div>'">
                        <?php else: ?>
                            <div class="lm-photo-placeholder">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($leader['role_title'])): ?>
                        <span class="lm-role-badge"><?php echo htmlspecialchars($leader['role_title']); ?></span>
                    <?php endif; ?>

                    <h3 class="lm-person-name"><?php echo htmlspecialchars($leader['name']); ?></h3>
                    <p class="lm-person-desig"><?php echo htmlspecialchars($leader['designation']); ?></p>
                </div>

                <!-- QUOTE SIDE -->
                <div class="lm-quote-side">
                    <div class="lm-quote-icon"><i class="fas fa-quote-left"></i></div>

                    <div class="lm-message-text">
                        <?php echo nl2br(htmlspecialchars($leader['message'])); ?>
                    </div>

                    <div class="lm-card-footer">
                        <?php if (!empty($leader['signature'])): ?>
                            <img src="<?php echo htmlspecialchars($leader['signature']); ?>"
                                 alt="Signature" class="lm-sig-img"
                                 loading="lazy"
                                 onerror="this.style.display='none'">
                            <div class="lm-sig-sep"></div>
                        <?php endif; ?>
                        <div>
                            <strong class="lm-footer-name"><?php echo htmlspecialchars($leader['name']); ?></strong>
                            <span class="lm-footer-role"><?php echo htmlspecialchars($leader['designation']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                endforeach;
            else:
            ?>
                <div class="lm-empty">
                    <i class="fas fa-users" style="font-size:36px;color:var(--border-color);display:block;margin-bottom:14px;"></i>
                    <p>No leadership messages available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Scroll Animation Script -->
<script>
(function() {
    var cards = document.querySelectorAll('.lm-animate-card');
    if (!cards.length) return;

    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry, idx) {
            if (entry.isIntersecting) {
                var el = entry.target;
                var delay = parseInt(el.getAttribute('data-delay') || 0);
                setTimeout(function() {
                    el.classList.add('lm-visible');
                }, delay);
                io.unobserve(el);
            }
        });
    }, { threshold: 0.15 });

    cards.forEach(function(card, i) {
        card.setAttribute('data-delay', i * 120);
        io.observe(card);
    });
})();
</script>
