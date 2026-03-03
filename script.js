// Navigasi internal: Show/hide section berdasarkan klik menu
document.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const sectionId = this.getAttribute('data-section');
        
        // Sembunyikan semua section
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Tampilkan section yang dipilih
        document.getElementById(sectionId).classList.add('active');
        
        // Smooth scroll ke atas halaman
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

// Validasi form di contact
const contactForm = document.querySelector('#contact form');
if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const name = contactForm.querySelector('input[name="name"]').value.trim();
        const email = contactForm.querySelector('input[name="email"]').value.trim();
        const message = contactForm.querySelector('textarea[name="message"]').value.trim();

        if (!name || !email || !message) {
            alert('Harap isi semua field sebelum mengirim pesan!');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Harap masukkan email yang valid!');
            return;
        }

        alert('Pesan Anda telah dikirim! Terima kasih.');
        contactForm.reset();
    });
}

// Animasi fade-in saat load
window.addEventListener('load', function () {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 1s';
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});