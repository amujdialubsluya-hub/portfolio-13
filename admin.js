// ===================================
// لوحة الإدارة - JavaScript
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    
    
    // إخفاء رسائل التنبيه بعد 5 ثواني
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // تأكيد الحذف
    const deleteButtons = document.querySelectorAll('[onclick*="confirm"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'هل أنت متأكد من هذا الإجراء؟';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // البحث في الجداول
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('.table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // ترتيب الجداول
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('.table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(this.parentElement.children).indexOf(this);
            const isAscending = this.classList.contains('asc');
            
            // إزالة الفئات من جميع العناوين
            sortableHeaders.forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            
            // إضافة الفئة للعنوان الحالي
            this.classList.add(isAscending ? 'desc' : 'asc');
            
            // ترتيب الصفوف
            rows.sort((a, b) => {
                const aValue = a.children[columnIndex].textContent.trim();
                const bValue = b.children[columnIndex].textContent.trim();
                
                if (isAscending) {
                    return bValue.localeCompare(aValue, 'ar');
                } else {
                    return aValue.localeCompare(bValue, 'ar');
                }
            });
            
            // إعادة ترتيب الصفوف في الجدول
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // تحميل الصور
    const imageInputs = document.querySelectorAll('input[type="file"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = input.parentElement.querySelector('.image-preview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // النماذج المتقدمة
    const advancedForms = document.querySelectorAll('.advanced-form');
    advancedForms.forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
        });
        
        // إعادة تفعيل الزر إذا كان هناك خطأ
        if (document.querySelector('.alert-error')) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // التبديل بين الحالات
    const statusToggles = document.querySelectorAll('.status-toggle');
    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const status = this.checked ? 'active' : 'inactive';
            
            fetch('toggle_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    status: status,
                    type: this.getAttribute('data-type')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('تم تحديث الحالة بنجاح', 'success');
                } else {
                    showNotification('حدث خطأ أثناء تحديث الحالة', 'error');
                    this.checked = !this.checked; // إعادة الحالة
                }
            })
            .catch(error => {
                showNotification('حدث خطأ في الاتصال', 'error');
                this.checked = !this.checked; // إعادة الحالة
            });
        });
    });

    // الإشعارات
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // إظهار الإشعار
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // إخفاء الإشعار تلقائياً
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        // إغلاق الإشعار يدوياً
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }

    // تصدير البيانات
    const exportButtons = document.querySelectorAll('.export-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format');
            const tableId = this.getAttribute('data-table');
            
            if (format === 'csv') {
                exportToCSV(tableId);
            } else if (format === 'excel') {
                exportToExcel(tableId);
            }
        });
    });

    function exportToCSV(tableId) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            cols.forEach(col => {
                rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
            });
            csv.push(rowData.join(','));
        });
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${tableId}_export.csv`;
        link.click();
    }

    function exportToExcel(tableId) {
        // يمكن إضافة مكتبة SheetJS لتصدير Excel
        showNotification('ميزة تصدير Excel قيد التطوير', 'info');
    }

    // الطباعة
    const printButtons = document.querySelectorAll('.print-btn');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const element = document.querySelector(target);
            
            if (element) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html dir="rtl">
                    <head>
                        <title>طباعة - بوابة الجامعات اليمنية</title>
                        <link rel="stylesheet" href="assets/css/admin.css">
                        <style>
                            body { font-family: 'Cairo', sans-serif; }
                            .no-print { display: none !important; }
                            @media print {
                                .sidebar, .page-header { display: none !important; }
                                .main-content { margin: 0 !important; }
                            }
                        </style>
                    </head>
                    <body>
                        ${element.outerHTML}
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        });
    });

    // التصميم المتجاوب للشريط الجانبي
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // إغلاق الشريط الجانبي عند النقر خارجه
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // تحميل البيانات بشكل تدريجي
    const loadMoreButtons = document.querySelectorAll('.load-more');
    loadMoreButtons.forEach(button => {
        button.addEventListener('click', function() {
            const container = this.getAttribute('data-container');
            const page = parseInt(this.getAttribute('data-page')) || 1;
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحميل...';
            this.disabled = true;
            
            fetch(`load_more.php?page=${page}&container=${container}`)
                .then(response => response.text())
                .then(html => {
                    const targetContainer = document.querySelector(container);
                    targetContainer.insertAdjacentHTML('beforeend', html);
                    
                    this.setAttribute('data-page', page + 1);
                    this.innerHTML = 'تحميل المزيد';
                    this.disabled = false;
                    
                    // إخفاء الزر إذا لم تعد هناك بيانات
                    if (html.trim() === '') {
                        this.style.display = 'none';
                    }
                })
                .catch(error => {
                    this.innerHTML = 'حدث خطأ، حاول مرة أخرى';
                    this.disabled = false;
                });
        });
    });

    // تحديث الإحصائيات في الوقت الفعلي
    function updateStats() {
        fetch('get_stats.php')
            .then(response => response.json())
            .then(data => {
                Object.keys(data).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = data[key];
                    }
                });
            })
            .catch(error => console.error('خطأ في تحديث الإحصائيات:', error));
    }

    // تحديث الإحصائيات كل 30 ثانية
    setInterval(updateStats, 30000);

    // تهيئة Tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            this.tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltip) {
                this.tooltip.remove();
                this.tooltip = null;
            }
        });
    });

    console.log('تم تحميل لوحة الإدارة بنجاح! 🎯');
}); 
