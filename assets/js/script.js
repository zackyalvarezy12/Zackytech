// Mobile Menu Toggle
const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Close menu when clicking on a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
    });
});

// Smooth Scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offsetTop = target.offsetTop - 80;
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
        }
    });
});

// Fade In Animation on Scroll - FIXED
const fadeElements = document.querySelectorAll('.fade-in');

const checkFade = () => {
    fadeElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
            element.classList.add('visible');
        }
    });
};

// Trigger immediately on page load
window.addEventListener('load', () => {
    checkFade();
    animateSkills();
});

window.addEventListener('scroll', checkFade);

// Initial check
setTimeout(() => {
    checkFade();
}, 100);

// Animate Skill Bars - FIXED
const animateSkills = () => {
    const skillBars = document.querySelectorAll('.skill-progress');
    skillBars.forEach(bar => {
        const progress = bar.getAttribute('data-progress');
        const skillItem = bar.closest('.skill-item');
        const skillTop = skillItem.getBoundingClientRect().top;
        
        if (skillTop < window.innerHeight - 100) {
            bar.style.width = progress + '%';
        }
    });
};

window.addEventListener('scroll', animateSkills);

// Form Submission to WhatsApp
const contactForm = document.getElementById('contactForm');
const whatsappNumber = '6281276429845'; // WhatsApp number with country code

contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const message = document.getElementById('message').value;
    
    // Format message for WhatsApp
    const whatsappMessage = `Halo, saya ${name}%0A%0AEmail: ${email}%0A%0APesan:%0A${message}`;
    
    // Open WhatsApp with pre-filled message
    window.open(`https://wa.me/${whatsappNumber}?text=${whatsappMessage}`, '_blank');
    
    // Reset form
    contactForm.reset();
});

// Active Nav Link on Scroll
window.addEventListener('scroll', () => {
    let current = '';
    const sections = document.querySelectorAll('section');
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (pageYOffset >= sectionTop - 100) {
            current = section.getAttribute('id');
        }
    });

    document.querySelectorAll('.nav-links a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').slice(1) === current) {
            link.classList.add('active');
        }
    });
});

// Portfolio Card Detail Button
document.querySelectorAll('.btn-detail').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const title = btn.closest('.portfolio-card').querySelector('.portfolio-title').textContent;
        alert(`Detail untuk proyek "${title}" akan segera ditampilkan dalam modal atau halaman terpisah.`);
    });
});

// Debug: Log when script loads
console.log('Script loaded successfully!');
console.log('Fade elements found:', fadeElements.length);