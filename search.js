// ========================================
// ملف JavaScript صفحة البحث - بوابة الجامعات اليمنية
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // عناصر البحث
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const searchButton = document.querySelector('.search-button');
    const searchTypeLabels = document.querySelectorAll('.search-type-label');
    
    // تحسين تجربة البحث
    function enhanceSearchExperience() {
        // التركيز التلقائي على حقل البحث
        if (searchInput && !searchInput.value) {
            searchInput.focus();
        }
        
        // البحث عند الضغط على Enter
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });
        }
        
        // تأثيرات على زر البحث
        if (searchButton) {
            searchButton.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            searchButton.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        }
        
        // تأثيرات على خيارات البحث
        searchTypeLabels.forEach(label => {
            label.addEventListener('click', function() {
                // إزالة التأثير من جميع الخيارات
                searchTypeLabels.forEach(l => {
                    l.style.transform = 'scale(1)';
                    l.style.background = 'var(--background-light)';
                    l.style.color = 'var(--text-primary)';
                });
                
                // إضافة التأثير للخيار المحدد
                this.style.transform = 'scale(1.05)';
                this.style.background = 'var(--primary-color)';
                this.style.color = 'white';
                
                // إعادة التأثير بعد ثانية
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
    }
    
    // تحسين عرض النتائج
    function enhanceResultsDisplay() {
        const resultCards = document.querySelectorAll('.university-card, .specialization-card');
        
        resultCards.forEach((card, index) => {
            // إضافة تأثير ظهور تدريجي
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
            
            // تأثيرات تفاعلية
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
                this.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
            });
        });
    }
    
    // تحسين قسم البحث السريع
    function enhanceQuickSearch() {
        const quickSearchItems = document.querySelectorAll('.quick-search-item');
        
        quickSearchItems.forEach((item, index) => {
            // تأثير ظهور تدريجي
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.6s ease-out';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 150);
            
            // تأثيرات تفاعلية
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
                this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.1)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = 'none';
            });
        });
    }
    
    // إضافة اقتراحات البحث
    function addSearchSuggestions() {
        const suggestions = [
            'جامعة صنعاء',
            'جامعة عدن',
            'كلية الطب',
            'كلية الهندسة',
            'تخصص طب بشري',
            'تخصص هندسة مدنية',
            'جامعات حكومية',
            'جامعات أهلية'
        ];
        
        if (searchInput) {
            // إضافة قائمة اقتراحات
            const suggestionsList = document.createElement('div');
            suggestionsList.className = 'search-suggestions';
            suggestionsList.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid var(--border-color);
                border-top: none;
                border-radius: 0 0 20px 20px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                display: none;
                max-height: 300px;
                overflow-y: auto;
            `;
            
            searchInput.parentElement.appendChild(suggestionsList);
            
            // إظهار الاقتراحات عند الكتابة
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                
                if (query.length > 0) {
                    const filteredSuggestions = suggestions.filter(suggestion => 
                        suggestion.toLowerCase().includes(query)
                    );
                    
                    if (filteredSuggestions.length > 0) {
                        suggestionsList.innerHTML = filteredSuggestions.map(suggestion => 
                            `<div class="suggestion-item" style="padding: 12px 20px; cursor: pointer; border-bottom: 1px solid var(--border-color); transition: background 0.2s;">${suggestion}</div>`
                        ).join('');
                        
                        suggestionsList.style.display = 'block';
                        
                        // إضافة تأثيرات للاقتراحات
                        const suggestionItems = suggestionsList.querySelectorAll('.suggestion-item');
                        suggestionItems.forEach(item => {
                            item.addEventListener('mouseenter', function() {
                                this.style.background = 'var(--background-light)';
                            });
                            
                            item.addEventListener('mouseleave', function() {
                                this.style.background = 'white';
                            });
                            
                            item.addEventListener('click', function() {
                                searchInput.value = this.textContent;
                                suggestionsList.style.display = 'none';
                                searchForm.submit();
                            });
                        });
                    } else {
                        suggestionsList.style.display = 'none';
                    }
                } else {
                    suggestionsList.style.display = 'none';
                }
            });
            
            // إخفاء الاقتراحات عند النقر خارجها
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsList.contains(e.target)) {
                    suggestionsList.style.display = 'none';
                }
            });
        }
    }
    
    // إضافة مؤشر تحميل
    function addLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'search-loading';
        loadingDiv.innerHTML = `
            <div class="loading-content">
                <i class="fas fa-spinner fa-spin"></i>
                <span>جاري البحث...</span>
            </div>
        `;
        loadingDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        `;
        
        const loadingContent = loadingDiv.querySelector('.loading-content');
        loadingContent.style.cssText = `
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            color: var(--primary-color);
            font-size: 1.1rem;
        `;
        
        document.body.appendChild(loadingDiv);
        
        // إظهار مؤشر التحميل عند البحث
        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                if (searchInput.value.trim()) {
                    loadingDiv.style.opacity = '1';
                    loadingDiv.style.visibility = 'visible';
                }
            });
        }
    }
    
    // تحسين رسالة عدم وجود نتائج
    function enhanceNoResults() {
        const noResults = document.querySelector('.no-results');
        
        if (noResults) {
            // إضافة تأثير ظهور
            noResults.style.opacity = '0';
            noResults.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                noResults.style.transition = 'all 0.8s ease-out';
                noResults.style.opacity = '1';
                noResults.style.transform = 'translateY(0)';
            }, 300);
            
            // إضافة تأثيرات للاقتراحات
            const suggestionItems = noResults.querySelectorAll('li');
            suggestionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease-out';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 500 + (index * 100));
            });
        }
    }
    
    // تحسين الإحصائيات
    
    
    // تهيئة جميع الوظائف
    function init() {
        enhanceSearchExperience();
        enhanceResultsDisplay();
        enhanceQuickSearch();
        addSearchSuggestions();
        addLoadingIndicator();
        enhanceNoResults();
        enhanceStats();
        
        console.log('✅ تم تحميل صفحة البحث بنجاح!');
    }
    
    // بدء التطبيق
    init();
});
