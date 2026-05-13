// PDF Export JavaScript
(function() {
    'use strict';
    
    // Auto-trigger print dialog when page loads
    window.addEventListener('load', function() {
        // Small delay to ensure everything is rendered
        setTimeout(function() {
            window.print();
        }, 500);
    });
    
    // Optional: Add page number to printed document
    function addPageNumbers() {
        const pages = document.querySelectorAll('.page-break');
        pages.forEach((page, index) => {
            const pageNum = document.createElement('div');
            pageNum.className = 'page-number';
            pageNum.textContent = `Page ${index + 1}`;
            pageNum.style.textAlign = 'center';
            pageNum.style.fontSize = '10px';
            pageNum.style.color = '#999';
            pageNum.style.marginTop = '20px';
            page.appendChild(pageNum);
        });
    }
    
    // Optional: Show print status
    function showPrintStatus() {
        const printBtn = document.querySelector('.print-button button');
        if (printBtn) {
            const originalText = printBtn.textContent;
            printBtn.textContent = '🖨️ Preparing PDF...';
            setTimeout(() => {
                printBtn.textContent = originalText;
            }, 1000);
        }
    }
    
    // Call functions
    showPrintStatus();
})();