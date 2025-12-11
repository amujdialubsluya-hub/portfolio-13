// ========================================
// ملف JavaScript صفحة الجامعات - بوابة الجامعات اليمنية
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // عناصر الفلترة
    const typeFilter = document.getElementById('type-filter');
    const locationFilter = document.getElementById('location-filter');
    const searchInput = document.getElementById('search-input');
    const universitiesGrid = document.getElementById('universities-grid');
    const universityCards = document.querySelectorAll('.university-card');
    
    // دالة فلترة الجامعات
    function filterUniversities() {
        const selectedType = typeFilter.value;
        const selectedLocation = locationFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        
        let visibleCount = 0;
        
        universityCards.forEach(card => {
            const type = card.dataset.type;
            const location = card.dataset.location;
            const name = card.dataset.name.toLowerCase();
            
            // التحقق من تطابق الفلاتر
            const typeMatch = !selectedType || type === selectedType;
            const locationMatch = !selectedLocation || location === selectedLocation;
            const searchMatch = !searchTerm || name.includes(searchTerm);
            
            // إظهار أو إخفاء البطاقة
            if (typeMatch && locationMatch && searchMatch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });
        
        // التحقق من وجود نتائج
        showNoResults(visibleCount === 0);
        
        // إضافة تأثير انتقالي للبطاقات المرئية
        const visibleCards = document.querySelectorAll('.university-card:not(.hidden)');
        visibleCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.style.animation = 'fadeIn 0.5s ease-out forwards';
        });
    }
    
    // دالة إظهار رسالة عدم وجود نتائج
    function showNoResults(show) {
        let noResults = document.querySelector('.no-results');
        
        if (show) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>لا توجد نتائج</h3>
                    <p>جرب تغيير معايير البحث</p>
                `;
                universitiesGrid.appendChild(noResults);
            }
        } else {
            if (noResults) {
                noResults.remove();
            }
        }
    }
    
    // إضافة مستمعي الأحداث للفلاتر
    if (typeFilter) {
        typeFilter.addEventListener('change', filterUniversities);
    }
    
    if (locationFilter) {
        locationFilter.addEventListener('change', filterUniversities);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterUniversities);
    }
    
    // تأثيرات إضافية للبطاقات
    universityCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // تحسين أداء البحث
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterUniversities, 200);
        });
    }
    
    // إضافة زر مسح الفلاتر
    function addClearFiltersButton() {
        const filterContainer = document.querySelector('.filter-container');
        if (filterContainer && !document.getElementById('clear-filters')) {
            const clearButton = document.createElement('button');
            clearButton.id = 'clear-filters';
            clearButton.className = 'btn btn-outline btn-sm';
            clearButton.innerHTML = '<i class="fas fa-times"></i> مسح الفلاتر';
            clearButton.style.marginTop = '35px';
            
            clearButton.addEventListener('click', function() {
                typeFilter.value = '';
                locationFilter.value = '';
                searchInput.value = '';
                filterUniversities();
            });
            
            filterContainer.appendChild(clearButton);
        }
    }
    
    // إضافة زر مسح الفلاتر عند التحميل
    addClearFiltersButton();
    
    // إضافة تأثيرات للفلاتر
    const filterElements = document.querySelectorAll('.filter-select, .filter-input');
    filterElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        element.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // ========================================
    // سلايدر معرض الصور - صفحة تفاصيل الجامعة
    // ========================================
    
    // التحقق من وجود السلايدر
    const gallerySlider = document.getElementById('gallerySlider');
    if (gallerySlider) {
        const slides = gallerySlider.querySelectorAll('.slide');
        const prevBtn = gallerySlider.querySelector('.slider-btn.prev');
        const nextBtn = gallerySlider.querySelector('.slider-btn.next');
        const dots = gallerySlider.querySelectorAll('.dot');
        const playPauseBtn = gallerySlider.querySelector('.play-pause-btn');
        const fullscreenBtn = gallerySlider.querySelector('.fullscreen-btn');
        
        let currentIndex = 0;
        const totalSlides = slides.length;
        
        // دالة تحديث السلايدر
        function updateSlider() {
            const translateX = -currentIndex * 100;
            const slidesContainer = gallerySlider.querySelector('.slides');
            slidesContainer.style.transform = `translateX(${translateX}%)`;
            
            // تحديث النقاط
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
            
            // تحديث حالة الأزرار
            prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
            nextBtn.style.opacity = currentIndex === totalSlides - 1 ? '0.5' : '1';
        }
        
        // الانتقال للشريحة التالية
        function nextSlide() {
            if (currentIndex < totalSlides - 1) {
                currentIndex++;
                updateSlider();
            }
        }
        
        // الانتقال للشريحة السابقة
        function prevSlide() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        }
        
        // الانتقال لشريحة محددة
        function goToSlide(index) {
            if (index >= 0 && index < totalSlides) {
                currentIndex = index;
                updateSlider();
            }
        }
        
        // إضافة مستمعي الأحداث
        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
        }
        
        // إضافة مستمعي الأحداث للنقاط
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });
        
        // وظائف الأزرار الإضافية
        let isPlaying = true;
        
        // زر التشغيل/الإيقاف
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                isPlaying = !isPlaying;
                if (isPlaying) {
                    startAutoPlay();
                    this.classList.remove('paused');
                    this.classList.add('playing');
                    this.querySelector('i').className = 'fas fa-pause';
                } else {
                    stopAutoPlay();
                    this.classList.remove('playing');
                    this.classList.add('paused');
                    this.querySelector('i').className = 'fas fa-play';
                }
            });
        }
        
        // زر العرض الكامل
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
                if (gallerySlider.requestFullscreen) {
                    gallerySlider.requestFullscreen();
                } else if (gallerySlider.webkitRequestFullscreen) {
                    gallerySlider.webkitRequestFullscreen();
                } else if (gallerySlider.msRequestFullscreen) {
                    gallerySlider.msRequestFullscreen();
                }
            });
        }
        
        // التنقل بلوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            if (gallerySlider.matches(':hover')) {
                if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextSlide();
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    prevSlide();
                }
            }
        });
        
        // التنقل باللمس للشاشات اللمسية
        let startX = 0;
        let endX = 0;
        
        gallerySlider.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        gallerySlider.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            
            if (Math.abs(diffX) > 50) { // الحد الأدنى للتمرير
                if (diffX > 0) {
                    nextSlide(); // تمرير لليسار = التالي
                } else {
                    prevSlide(); // تمرير لليمين = السابق
                }
            }
        });
        
        // التشغيل التلقائي (اختياري)
        let autoPlayInterval;
        
        function startAutoPlay() {
            autoPlayInterval = setInterval(() => {
                if (currentIndex < totalSlides - 1) {
                    nextSlide();
                } else {
                    currentIndex = 0;
                    updateSlider();
                }
            }, 5000); // 5 ثوان
        }
        
        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
        }
        
        // بدء التشغيل التلقائي عند تحميل الصفحة
        if (totalSlides > 1) {
            startAutoPlay();
            
            // إيقاف التشغيل التلقائي عند التفاعل
            gallerySlider.addEventListener('mouseenter', stopAutoPlay);
            gallerySlider.addEventListener('mouseleave', startAutoPlay);
        }
        
        // تحديث السلايدر عند التحميل
        updateSlider();
        
        console.log('✅ تم تحميل سلايدر معرض الصور بنجاح!');
    }
    
    console.log('✅ تم تحميل صفحة الجامعات بنجاح!');
});
