/**
 * FAQ Frontend JavaScript
 * Handles FAQ accordion functionality with smooth animations
 */

// Import FAQ styles
import '../../css/modules/faq.scss';

class FaqManager {
    constructor() {
        this.init();
    }

    init() {
        // Initialize FAQ functionality when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initFaq());
        } else {
            this.initFaq();
        }
    }

    /**
     * Initialize FAQ accordion functionality
     */
    initFaq() {
        this.attachEventListeners();
        this.handleUrlHash();
    }

    /**
     * Attach click event listeners to FAQ questions
     */
    attachEventListeners() {
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', (e) => this.toggleFaq(e.currentTarget));
        });
    }

    /**
     * Toggle FAQ answer visibility with animation
     * @param {HTMLElement} questionElement - The clicked question element
     */
    toggleFaq(questionElement) {
        const faqId = questionElement.getAttribute('data-faq-id');
        const answer = document.getElementById('answer-' + faqId);
        const icon = questionElement.querySelector('.faq-toggle-icon');
        
        if (answer.style.display === 'none' || answer.style.display === '') {
            this.showAnswer(answer, icon, faqId);
        } else {
            this.hideAnswer(answer, icon);
        }
    }

    /**
     * Show FAQ answer with slide down animation
     * @param {HTMLElement} answer - Answer element
     * @param {HTMLElement} icon - Toggle icon element
     * @param {string} faqId - FAQ ID for URL hash
     */
    showAnswer(answer, icon, faqId) {
        answer.style.display = 'block';
        answer.style.opacity = '0';
        answer.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            answer.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            answer.style.opacity = '1';
            answer.style.transform = 'translateY(0)';
        }, 10);
        
        icon.classList.add('rotated');
        
        // Update URL hash
        history.pushState(null, null, '#' + faqId);
    }

    /**
     * Hide FAQ answer with slide up animation
     * @param {HTMLElement} answer - Answer element
     * @param {HTMLElement} icon - Toggle icon element
     */
    hideAnswer(answer, icon) {
        answer.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        answer.style.opacity = '0';
        answer.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            answer.style.display = 'none';
        }, 300);
        
        icon.classList.remove('rotated');
        
        // Remove URL hash
        history.pushState(null, null, window.location.pathname + window.location.search);
    }

    /**
     * Handle URL hash to auto-expand specific FAQ
     */
    handleUrlHash() {
        if (window.location.hash) {
            const faqId = window.location.hash.substring(1);
            const question = document.querySelector('[data-faq-id="' + faqId + '"]');
            if (question) {
                question.click();
            }
        }
    }
}

// Initialize FAQ manager
const faqManager = new FaqManager();

// Export for potential external use
export default FaqManager;