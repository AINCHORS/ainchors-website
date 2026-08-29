const cardsPerView = () => {
    if (window.innerWidth >= 1200) return 4;
    if (window.innerWidth >= 900) return 3;
    if (window.innerWidth >= 640) return 2;
    return 1;
};

class CourseCarousel {
    constructor(root) {
        this.root = root;
        this.viewport = root.querySelector('[data-carousel-viewport]');
        this.track = root.querySelector('[data-carousel-track]');
        this.cards = [...root.querySelectorAll('[data-carousel-card]')];
        this.navigation = root.querySelector('[data-carousel-navigation]');
        this.pagination = root.querySelector('[data-carousel-pagination]');
        this.previous = root.querySelector('[data-carousel-previous]');
        this.next = root.querySelector('[data-carousel-next]');
        this.status = root.querySelector('[data-carousel-status]');
        this.page = 0;
        this.perView = 1;
        this.pages = 1;
        this.scrollFrame = null;
        this.resizeFrame = null;
        this.pointerStartX = null;
        this.pointerStartScroll = 0;
        this.dragDistance = 0;

        if (!this.viewport || !this.track || this.cards.length === 0) return;

        this.previous?.addEventListener('click', () => this.goTo(this.page - 1));
        this.next?.addEventListener('click', () => this.goTo(this.page + 1));
        this.viewport.addEventListener('scroll', () => this.onScroll(), { passive: true });
        this.viewport.addEventListener('keydown', (event) => this.onKeydown(event));
        this.viewport.addEventListener('pointerdown', (event) => this.onPointerDown(event));
        this.viewport.addEventListener('pointermove', (event) => this.onPointerMove(event));
        this.viewport.addEventListener('pointerup', (event) => this.onPointerUp(event));
        this.viewport.addEventListener('pointercancel', (event) => this.onPointerUp(event));
        this.viewport.addEventListener('click', (event) => {
            if (this.dragDistance > 8) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.dragDistance = 0;
        }, true);
        window.addEventListener('resize', () => this.onResize(), { passive: true });

        this.recalculate(false);
    }

    recalculate(preservePosition = true) {
        const firstVisibleCard = preservePosition ? this.page * this.perView : 0;
        this.perView = cardsPerView();
        this.pages = Math.max(1, Math.ceil(this.cards.length / this.perView));
        this.page = Math.min(this.pages - 1, Math.floor(firstVisibleCard / this.perView));

        this.cards.forEach((card, index) => {
            card.dataset.pageStart = index % this.perView === 0 ? 'true' : 'false';
        });

        this.track.style.setProperty('--course-carousel-end-space', '0px');
        this.track.dataset.hasEndSpacer = 'false';
        const cardWidth = this.cards[0]?.getBoundingClientRect().width || 0;
        const gap = parseFloat(getComputedStyle(this.track).columnGap) || 0;
        if (this.perView === 1 && this.cards.length > 1) {
            this.track.style.setProperty('--course-carousel-end-space', `${Math.max(0, this.viewport.clientWidth - cardWidth - gap)}px`);
            this.track.dataset.hasEndSpacer = 'true';
        } else if (this.perView > 1 && this.cards.length % this.perView !== 0) {
            const missingCards = this.perView - (this.cards.length % this.perView);
            this.track.style.setProperty('--course-carousel-end-space', `${Math.max(0, (missingCards * (cardWidth + gap)) - gap)}px`);
            this.track.dataset.hasEndSpacer = 'true';
        }

        this.renderPagination();
        this.goTo(this.page, false);
    }

    renderPagination() {
        const navigationRequired = this.cards.length > this.perView;
        this.navigation.hidden = !navigationRequired;
        this.pagination.replaceChildren();

        if (!navigationRequired) return;

        for (let page = 0; page < this.pages; page += 1) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'course-carousel-dot';
            dot.setAttribute('aria-label', `Go to course page ${page + 1}`);
            dot.addEventListener('click', () => this.goTo(page));
            this.pagination.append(dot);
        }
    }

    goTo(page, smooth = true) {
        this.page = Math.max(0, Math.min(page, this.pages - 1));
        const card = this.cards[this.page * this.perView];
        const left = card ? card.offsetLeft - this.cards[0].offsetLeft : 0;
        this.viewport.scrollTo({ left, behavior: smooth ? 'smooth' : 'auto' });
        this.updateControls();
    }

    updateControls() {
        if (this.previous) {
            this.previous.disabled = this.page === 0;
            this.previous.setAttribute('aria-disabled', String(this.page === 0));
        }
        if (this.next) {
            this.next.disabled = this.page === this.pages - 1;
            this.next.setAttribute('aria-disabled', String(this.page === this.pages - 1));
        }

        [...this.pagination.children].forEach((dot, index) => {
            const current = index === this.page;
            dot.classList.toggle('is-active', current);
            if (current) dot.setAttribute('aria-current', 'page');
            else dot.removeAttribute('aria-current');
        });

        if (this.status && !this.navigation.hidden) {
            this.status.textContent = `Course page ${this.page + 1} of ${this.pages}`;
        }

    }

    onScroll() {
        if (this.scrollFrame) cancelAnimationFrame(this.scrollFrame);
        this.scrollFrame = requestAnimationFrame(() => {
            const groupStarts = this.cards.filter((_, index) => index % this.perView === 0);
            const nearest = groupStarts.reduce((best, card, index) => {
                const target = card.offsetLeft - this.cards[0].offsetLeft;
                const distance = Math.abs(target - this.viewport.scrollLeft);
                return distance < best.distance ? { index, distance } : best;
            }, { index: 0, distance: Number.POSITIVE_INFINITY });
            this.page = Math.min(nearest.index, this.pages - 1);
            this.updateControls();
        });
    }

    onResize() {
        if (this.resizeFrame) cancelAnimationFrame(this.resizeFrame);
        this.resizeFrame = requestAnimationFrame(() => this.recalculate(true));
    }

    onKeydown(event) {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            this.goTo(this.page - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            this.goTo(this.page + 1);
        }
    }

    onPointerDown(event) {
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        if (event.target.closest('a, button, input, select, textarea, label')) return;
        this.pointerStartX = event.clientX;
        this.pointerStartScroll = this.viewport.scrollLeft;
        this.dragDistance = 0;
        this.viewport.setPointerCapture(event.pointerId);
    }

    onPointerMove(event) {
        if (this.pointerStartX === null) return;
        this.dragDistance = Math.abs(event.clientX - this.pointerStartX);
        if (this.dragDistance > 4) {
            this.viewport.classList.add('is-dragging');
            this.viewport.scrollLeft = this.pointerStartScroll - (event.clientX - this.pointerStartX);
        }
    }

    onPointerUp(event) {
        if (this.pointerStartX === null) return;
        this.pointerStartX = null;
        this.viewport.classList.remove('is-dragging');
        if (this.viewport.hasPointerCapture(event.pointerId)) this.viewport.releasePointerCapture(event.pointerId);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-course-carousel]').forEach((carousel) => new CourseCarousel(carousel));
});
