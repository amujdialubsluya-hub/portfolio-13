// ========================================
// ملف JavaScript صفحة التخصصات - بوابة الجامعات اليمنية
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // عناصر الفلترة
    const universityFilter = document.getElementById('university-filter');
    const collegeFilter = document.getElementById('college-filter');
    const searchInput = document.getElementById('search-input');
    const specializationsGrid = document.getElementById('specializations-grid');
    const specializationCards = document.querySelectorAll('.specialization-card');
    
    // دالة فلترة التخصصات
    function filterSpecializations() {
        const selectedUniversity = universityFilter.value;
        const selectedCollege = collegeFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        
        let visibleCount = 0;
        
        specializationCards.forEach(card => {
            const university = card.dataset.university;
            const college = card.dataset.college;
            const name = card.dataset.name.toLowerCase();
            
            // التحقق من تطابق الفلاتر
            const universityMatch = !selectedUniversity || university === selectedUniversity;
            const collegeMatch = !selectedCollege || college.includes(selectedCollege);
            const searchMatch = !searchTerm || name.includes(searchTerm);
            
            // إظهار أو إخفاء البطاقة
            if (universityMatch && collegeMatch && searchMatch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });
        
        // التحقق من وجود نتائج
        showNoResults(visibleCount === 0);
        
        // إضافة تأثير انتقالي للبطاقات المرئية
        const visibleCards = document.querySelectorAll('.specialization-card:not(.hidden)');
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
                specializationsGrid.appendChild(noResults);
            }
        } else {
            if (noResults) {
                noResults.remove();
            }
        }
    }
    
    // إضافة مستمعي الأحداث للفلاتر
    if (universityFilter) {
        universityFilter.addEventListener('change', filterSpecializations);
    }
    
    if (collegeFilter) {
        collegeFilter.addEventListener('change', filterSpecializations);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterSpecializations);
    }
    
    // تأثيرات إضافية للبطاقات
    specializationCards.forEach(card => {
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
            searchTimeout = setTimeout(filterSpecializations, 200);
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
            clearButton.style.marginTop = '34px';
            
            clearButton.addEventListener('click', function() {
                universityFilter.value = '';
                collegeFilter.value = '';
                searchInput.value = '';
                filterSpecializations();
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
    
    // تأثيرات للتخصصات الأكثر شعبية
    const popularItems = document.querySelectorAll('.popular-item');
    popularItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.style.animation = 'fadeIn 0.6s ease-out forwards';
        
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // تحسين تجربة المستخدم - إضافة مؤشر تحميل
    function showLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-indicator';
        loadingDiv.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span>جاري البحث...</span>
            </div>
        `;
        loadingDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
        `;
        
        const spinnerStyle = `
            .loading-spinner {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 1rem;
                color: var(--primary-color);
            }
        `;
        
        const style = document.createElement('style');
        style.textContent = spinnerStyle;
        document.head.appendChild(style);
        
        document.body.appendChild(loadingDiv);
        return loadingDiv;
    }
    
    const loadingIndicator = showLoadingIndicator();
    
    // إظهار مؤشر التحميل عند البحث
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                loadingIndicator.style.display = 'block';
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 300);
            }
        });
    }
    
    console.log('✅ تم تحميل صفحة التخصصات بنجاح!');
});
