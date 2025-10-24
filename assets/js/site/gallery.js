/**
 * Gallery Frontend JavaScript
 * Handles gallery lightbox initialization and event filtering
 */

class GalleryManager {
    constructor() {
        this.init();
    }

    init() {
        // Initialize GLightbox when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initLightbox());
        } else {
            this.initLightbox();
        }

        // Make event filtering functions globally available for onclick handlers
        window.showAllEvents = this.showAllEvents.bind(this);
        window.showEvent = this.showEvent.bind(this);
    }

    /**
     * Initialize GLightbox for image galleries
     */
    initLightbox() {
        if (typeof GLightbox !== 'undefined') {
            const lightbox = GLightbox({
                touchNavigation: true,
                loop: true,
                autoplayVideos: false,
                closeButton: true,
                zoomable: true,
                draggable: true
            });
        } else {
            console.warn('GLightbox is not loaded. Make sure to include GLightbox library.');
        }
    }

    /**
     * Show all events (remove filtering)
     */
    showAllEvents() {
        document.querySelectorAll('.event-section').forEach(section => {
            section.style.display = 'block';
        });
        this.setActiveButton('Alle Events');
    }

    /**
     * Show specific event only
     * @param {string} eventName - Name of the event to show
     */
    showEvent(eventName) {
        document.querySelectorAll('.event-section').forEach(section => {
            if (section.dataset.event === eventName) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
        this.setActiveButton(eventName);
    }

    /**
     * Set active state for navigation buttons
     * @param {string} activeEvent - Name of the active event or 'Alle Events'
     */
    setActiveButton(activeEvent) {
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.classList.remove('active');
            
            if (btn.textContent.includes('Alle Events') && activeEvent === 'Alle Events') {
                btn.classList.add('active');
            } else if (activeEvent !== 'Alle Events' && btn.onclick && btn.onclick.toString().includes(`'${activeEvent}'`)) {
                btn.classList.add('active');
            }
        });
    }
}

// Initialize gallery manager
const galleryManager = new GalleryManager();

// Export for potential external use
export default GalleryManager;