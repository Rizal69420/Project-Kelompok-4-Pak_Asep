// Real-time API helper
class HospitalAPI {
    constructor() {
        this.apiUrl = '../api/handler.php';
    }

    // Get user appointments with real-time updates
    async getUserAppointments() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_user_appointments`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching appointments:', error);
            return null;
        }
    }

    // Get appointment statistics (admin)
    async getAppointmentStats() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_appointment_stats`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching stats:', error);
            return null;
        }
    }

    // Get unread messages (admin)
    async getUnreadMessages() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_unread_messages`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching messages:', error);
            return null;
        }
    }

    // Get doctors list
    async getDoctors() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_doctors`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching doctors:', error);
            return null;
        }
    }

    // Get services list
    async getServices() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_services`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching services:', error);
            return null;
        }
    }

    // Book appointment
    async bookAppointment(doctorId, serviceId, appointmentDate, notes) {
        try {
            const formData = new FormData();
            formData.append('doctor_id', doctorId);
            formData.append('service_id', serviceId);
            formData.append('appointment_date', appointmentDate);
            formData.append('notes', notes);

            const response = await fetch(`${this.apiUrl}?action=book_appointment`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error booking appointment:', error);
            return { success: false, error: 'Failed to book appointment' };
        }
    }

    // Update appointment status (admin)
    async updateAppointmentStatus(appointmentId, status) {
        try {
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('status', status);

            const response = await fetch(`${this.apiUrl}?action=update_appointment_status`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error updating status:', error);
            return { success: false, error: 'Failed to update status' };
        }
    }
}

// Initialize API
const hospitalAPI = new HospitalAPI();

// Real-time updates for user appointments
function initializeUserRealtime() {
    // Update appointments every 30 seconds
    setInterval(async () => {
        const result = await hospitalAPI.getUserAppointments();
        if (result && result.success) {
            updateAppointmentUI(result.appointments);
        }
    }, 30000);
}

// Update appointment UI with real-time data
function updateAppointmentUI(appointments) {
    const appointmentTable = document.querySelector('table tbody');
    if (!appointmentTable) return;

    // This would update the table with real-time data
    console.log('Updating appointments:', appointments);
}

// Real-time updates for admin dashboard
function initializeAdminRealtime() {
    // Update stats every 30 seconds
    setInterval(async () => {
        const result = await hospitalAPI.getAppointmentStats();
        if (result && result.success) {
            updateStatsUI(result);
        }
    }, 30000);

    // Update messages every 60 seconds
    setInterval(async () => {
        const result = await hospitalAPI.getUnreadMessages();
        if (result && result.success) {
            updateMessageBadge(result.unread_count);
        }
    }, 60000);
}

// Update stats UI
function updateStatsUI(stats) {
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-card .number');
    if (statCards.length >= 5) {
        statCards[0].textContent = stats.total;
        statCards[1].textContent = stats.pending;
        // Add more updates as needed
    }
}

// Update message badge
function updateMessageBadge(count) {
    const badge = document.querySelector('[data-unread-count]');
    if (badge) {
        badge.textContent = count;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Determine if we're on user or admin page
    if (document.body.classList.contains('admin-page')) {
        initializeAdminRealtime();
    } else if (document.body.classList.contains('user-page')) {
        initializeUserRealtime();
    }

    // Initialize form handlers
    const bookingForm = document.querySelector('[name="book_appointment"]');
    if (bookingForm) {
        initializeBookingForm();
    }
});

// Initialize booking form with AJAX
function initializeBookingForm() {
    const form = document.querySelector('form[name="book_appointment"]');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        // Allow normal form submission for now
        // but add real-time validation here if needed
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

// Format date/time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { hospitalAPI, showNotification, formatCurrency, formatDateTime };
}
