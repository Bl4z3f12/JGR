function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = overlay.querySelector('.loading-message');
    const randomMessageNum = Math.floor(Math.random() * 5) + 1;
    messageEl.setAttribute('data-message', randomMessageNum);
    overlay.classList.add('show');
    return new Promise(resolve => setTimeout(resolve, 800));
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.remove('show');
}