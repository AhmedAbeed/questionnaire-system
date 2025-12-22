class SessionManager {
    /**
     * Creates a new SessionManager instance
     * @param {Object} routes - Routes for session endpoints
     * @param {number} sessionWarningThreshold - Warning threshold for session expiry in seconds
     * @param {number} idleTimeout - Idle timeout duration in milliseconds
     * @param {number} idleWarningThreshold - Warning threshold for idle timeout in milliseconds
     */
    constructor(routes, sessionWarningThreshold, idleTimeout, idleWarningThreshold) {
        this.routes = routes;
        this.sessionWarningThreshold = sessionWarningThreshold;
        this.idleTimeCounter = 0;
        this.idleTimeout = idleTimeout;
        this.warningDisplayed = false;
        this.idleWarningDisplayed = false;
        this.checkTimer = null;
        this.idleTimer = null;
        this.boundResetTimers = this.resetTimers.bind(this);
        this.init();
    }

    /**
     * Initialize session management
     */
    init() {
        this.scheduleSessionCheck();
        this.setupEventListeners();
        this.startIdleTracking();
    }

    /**
     * Schedule periodic session time checks (every 60 seconds)
     */
    scheduleSessionCheck() {
        if (this.checkTimer) {
            clearTimeout(this.checkTimer);
        }

        this.checkTimer = setTimeout(() => {
            this.checkSessionTime();
        }, 60 * 1000);
    }

    /**
     * Check remaining session time with the server
     */
    async checkSessionTime() {
        try {
            const response = await fetch(this.routes.remaining, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.expired) {
                this.handleSessionEnd('expired');
                return;
            }

            const remainingTime = data.remaining_seconds;

            // Fixed: Using this.sessionWarningThreshold instead of sessionWarningThreshold
            if (remainingTime <= this.sessionWarningThreshold && remainingTime > 0 && !this.warningDisplayed) {
                this.showSessionWarning(remainingTime);
                this.warningDisplayed = true;
            }

            this.scheduleSessionCheck();
        } catch (error) {
            this.scheduleSessionCheck();
        }
    }

    /**
     * Start tracking user idle time
     */
    startIdleTracking() {
        if (this.idleTimer) {
            clearInterval(this.idleTimer);
        }

        this.idleTimer = setInterval(() => {
            this.idleTimeCounter += 1000;

            if (this.idleTimeCounter >= (this.idleTimeout - this.idleWarningThreshold) &&
                this.idleTimeCounter < this.idleTimeout &&
                !this.idleWarningDisplayed) {
                this.showIdleWarning();
                this.idleWarningDisplayed = true;
            }

            if (this.idleTimeCounter >= this.idleTimeout) {
                this.handleSessionEnd("idle");
            }
        }, 1000);
    }

    /**
     * Display idle warning message to the user
     */
    showIdleWarning() {
        const remainingSeconds = Math.floor((this.idleTimeout - this.idleTimeCounter) / 1000);
        const warningMessage = `You've been inactive for a while. Your session will expire in ${remainingSeconds} seconds due to inactivity.`;
    
        let timerInterval;
        
        if (Swal.isVisible()) {
            Swal.close();
        }
        
        Swal.fire({
            title: "Inactivity Detected",
            html: `${warningMessage}<br><br>Time remaining: <b></b> seconds`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Continue Session",
            cancelButtonText: "Log Out",
            timer: 60000,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
                const timer = Swal.getPopup().querySelector("b");
                if (timer) {
                    timerInterval = setInterval(() => {
                        const remainingTime = Math.ceil(Swal.getTimerLeft() / 1000);
                        timer.textContent = remainingTime;
                    }, 100);
                }
            },
            willClose: () => {
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
            }
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.timer) {
                this.handleSessionEnd("idle");
            } else if (result.isConfirmed) {
                this.resetTimers();
                this.idleWarningDisplayed = false;
            } else {
                this.handleSessionEnd("logout");
            }
        });
    }

    /**
     * Display session warning with extend/cancel options
     * @param {number} remainingTime - Remaining time in seconds
     */
    showSessionWarning(remainingTime) {
        const warningMessage = `Your session will expire in ${remainingTime} seconds. Would you like to extend it ? `;
    
        let timerInterval;
        
        if (Swal.isVisible()) {
            Swal.close();
        }
        
        Swal.fire({
            title: "Session Expiring Soon",
            html: `${warningMessage}<br><br>Time remaining: <b></b> seconds`,
            icon: "warning",
            showCancelButton: true,
            showConfirmButton: true,
            allowOutsideClick: false,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Extend Session",
            cancelButtonText: "Log Out",
            timer: 60000,
            timerProgressBar: true,
            didOpen: () => {
                // Removed Swal.showLoading()
                const timer = Swal.getPopup().querySelector("b");
                if (timer) {
                    timerInterval = setInterval(() => {
                        const remainingTime = Math.ceil(Swal.getTimerLeft() / 1000);
                        timer.textContent = remainingTime;
                    }, 100);
                }
            },
            willClose: () => {
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
            }
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.timer) {
                this.handleSessionEnd("expired");
            } else if (result.isConfirmed) {
                this.extendSession();
                this.warningDisplayed = false;
            } else {
                this.handleSessionEnd("logout");
            }
        });
    }

    /**
     * Show feedback message to the user
     * @param {string} title - Title of the feedback message
     * @param {string} message - Feedback message content
     * @param {string} icon - Icon type (success, error, warning, info)
     */
    showFeedback(title, message, icon = "info") {
        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }

    /**
     * Handle session end (timeout or user choice)
     * @param {string} reason - Reason for session end (idle, expired, logout)
     */
    handleSessionEnd(reason) {
        this.destroy();
        
        if (reason === "idle") {
            // Fixed: Corrected typo in route name
            window.location.href = this.routes.idleTimeout;
        } else if (reason === "expired" || reason === "logout") {
            window.location.href = this.routes.timeout;
        } else {
            // Default fallback
            window.location.href = this.routes.timeout;
        }
    }

    /**
     * Extend the user's session
     */
    async extendSession() {
        try {
            const response = await fetch(this.routes.extend, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Added: Feedback to user for successful session extension
                this.showFeedback("Session Extended", "Your session has been extended successfully.", "success");
            } else {
                // Added: Better error feedback to user
                this.showFeedback("Session Extension Failed", data.message || "Failed to extend session. Please refresh the page.", "error");
            }
        } catch (error) {
            this.showFeedback("Error", "An error occurred while extending your session. Please try again or refresh the page.", "error");
        }
    }

    /**
     * Reset idle timers based on user activity
     */
    resetTimers() {
        this.idleTimeCounter = 0;
        this.idleWarningDisplayed = false;
    }

    /**
     * Setup event listeners for user activity
     */
    setupEventListeners() {
        ['click', 'mousemove', 'keydown'].forEach(event => {
            document.addEventListener(event, this.boundResetTimers);
        });
    }

    /**
     * Clean up resources when the manager is no longer needed
     */
    destroy() {
        if (this.checkTimer) clearTimeout(this.checkTimer);
        if (this.idleTimer) clearInterval(this.idleTimer);

        ['click', 'mousemove', 'keydown'].forEach(event => {
            document.removeEventListener(event, this.boundResetTimers);
        });
    }
}