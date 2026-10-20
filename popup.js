const API_BASE = 'http://localhost:8000';

async function getCurrentHost() {
    const [tab] = await chrome.tabs.query({active: true, currentWindow: true});
    try {
        const url = new URL(tab.url);
        return url.hostname;
    } catch {
        return 'Restricted page';
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const host = await getCurrentHost();
    document.getElementById('host').textContent = host;

    // Check backend connectivity
    try {
        const health = await fetch(`${API_BASE}/health.php`).then(r => r.json());
        document.getElementById('conn').textContent = health.db === 'connected' ? '🟢' : '🔴';
        document.getElementById('conn').title = health.db === 'connected' ? 'Backend connected' : 'Backend error';
    } catch {
        document.getElementById('conn').textContent = '🔴';
        document.getElementById('conn').title = 'Backend unreachable';
    }

    document.getElementById('scanBtn').onclick = async () => {
        const btn = document.getElementById('scanBtn');
        const resultDiv = document.getElementById('result');

        if (host === 'Restricted page') {
            resultDiv.innerHTML = '<div class="risk" style="color:var(--sub)">Cannot scan this page (browser internal page)</div>';
            resultDiv.classList.remove('hidden');
            return;
        }

        btn.textContent = 'Scanning...';
        btn.disabled = true;
        resultDiv.classList.add('hidden');

        try {
            const res = await fetch(`${API_BASE}/dns_scan.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({domain: host})
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();

            const r = data.results;
            resultDiv.innerHTML = `
                <div class="risk">
                    <div><strong>Risk Score:</strong> ${r.risk_score}/100</div>
                    <div><strong>Severity:</strong> <span style="color:${r.severity==='High'?'var(--high)':r.severity==='Medium'?'var(--med)':'var(--low)'}">${r.severity}</span></div>
                    <div style="margin-top:8px;font-size:12px;color:var(--sub)">${r.records.A?.length||0} A, ${r.records.AAAA?.length||0} AAAA, ${r.records.MX?.length||0} MX, ${r.records.NS?.length||0} NS, ${r.records.TXT?.length||0} TXT</div>
                </div>
                <button onclick="window.open('http://localhost:8001/reports.html')" class="primary" style="margin-top:10px;width:100%">View Full Report</button>
            `;
            resultDiv.classList.remove('hidden');
        } catch (e) {
            resultDiv.innerHTML = `<div class="risk" style="color:var(--high)">Scan failed: ${e.message}</div>`;
            resultDiv.classList.remove('hidden');
        } finally {
            btn.textContent = 'Scan This Website';
            btn.disabled = false;
        }
    };
});