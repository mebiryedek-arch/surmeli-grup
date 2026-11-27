(function () {
    'use strict';
    const root = document.querySelector('.sg-app');
    if (!root || !root.querySelector('[data-section-view="reports"]') || !window.SG_APP) return;

    const $ = (id) => root.querySelector('#' + id);
    const endpoint = SG_APP.reportsEndpoint;
    const drillEndpoint = SG_APP.reportsDrilldownEndpoint;
    const exportEndpoint = SG_APP.reportsExportEndpoint;
    if (!endpoint || !drillEndpoint || !exportEndpoint) return;

    const money = (value) => new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 2 }).format(Number(value || 0));
    const number = (value) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 1 }).format(Number(value || 0));
    const dateText = (value) => {
        if (!value) return '—';
        const d = new Date(String(value).slice(0, 10) + 'T12:00:00');
        return Number.isNaN(d.getTime()) ? String(value) : new Intl.DateTimeFormat('tr-TR').format(d);
    };
    const esc = (value) => String(value == null ? '' : value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const localDate = (d) => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
    const parseDate = (v) => { const a = String(v).split('-').map(Number); return new Date(a[0], a[1] - 1, a[2], 12); };
    const api = async (url) => {
        const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-WP-Nonce': SG_APP.nonce, Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Rapor verisi alınamadı.');
        return data;
    };
    const paramsFor = (extra = {}) => {
        const p = new URLSearchParams({ from: $('sg-report-from').value, to: $('sg-report-to').value });
        Object.entries(extra).forEach(([k, v]) => { if (v !== '' && v != null) p.set(k, v); });
        return p;
    };

    let report = null;
    let activeTab = 'overview';
    let activeDetail = 'income';

    const setPeriod = (preset) => {
        const now = new Date();
        let from = new Date(now.getFullYear(), now.getMonth(), 1, 12);
        let to = new Date(now.getFullYear(), now.getMonth() + 1, 0, 12);
        if (preset === 'today') from = to = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 12);
        if (preset === 'yesterday') from = to = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 12);
        if (preset === 'week') {
            const day = (now.getDay() + 6) % 7;
            from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day, 12);
            to = new Date(from); to.setDate(from.getDate() + 6);
        }
        if (preset === 'prev_month') { from = new Date(now.getFullYear(), now.getMonth() - 1, 1, 12); to = new Date(now.getFullYear(), now.getMonth(), 0, 12); }
        if (preset === 'quarter') { const q = Math.floor(now.getMonth() / 3) * 3; from = new Date(now.getFullYear(), q, 1, 12); to = new Date(now.getFullYear(), q + 3, 0, 12); }
        if (preset === 'year') { from = new Date(now.getFullYear(), 0, 1, 12); to = new Date(now.getFullYear(), 11, 31, 12); }
        if (preset === 'prev_year') { from = new Date(now.getFullYear() - 1, 0, 1, 12); to = new Date(now.getFullYear() - 1, 11, 31, 12); }
        $('sg-report-from').value = localDate(from);
        $('sg-report-to').value = localDate(to);
    };

    const comparisonParams = () => {
        if (!$('sg-report-compare').checked) return {};
        const from = parseDate($('sg-report-from').value);
        const to = parseDate($('sg-report-to').value);
        const days = Math.round((to - from) / 86400000) + 1;
        const prevTo = new Date(from); prevTo.setDate(prevTo.getDate() - 1);
        const prevFrom = new Date(prevTo); prevFrom.setDate(prevTo.getDate() - days + 1);
        return { compare_from: localDate(prevFrom), compare_to: localDate(prevTo) };
    };

    const setText = (id, value) => { const el = $(id); if (el) el.textContent = value; };
    const renderBars = (id, rows, label, value, onClick, formatter = money) => {
        const host = $(id); if (!host) return;
        host.replaceChildren();
        if (!rows || !rows.length) { const empty = document.createElement('p'); empty.className = 'sg-report-empty'; empty.textContent = 'Bu tarih aralığında kayıt bulunamadı.'; host.appendChild(empty); return; }
        const max = Math.max(1, ...rows.map((r) => Math.abs(Number(value(r) || 0))));
        rows.forEach((row) => {
            const v = Number(value(row) || 0);
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'sg-report-bar-row';
            button.title = label(row) + ': ' + formatter(v) + ' — ayrıntıyı aç';
            const top = document.createElement('span'); top.className = 'sg-report-bar-label'; top.textContent = label(row);
            const track = document.createElement('span'); track.className = 'sg-report-bar-track';
            const fill = document.createElement('span'); fill.className = 'sg-report-bar-fill'; fill.style.width = Math.max(1, Math.round(Math.abs(v) / max * 100)) + '%'; track.appendChild(fill);
            const val = document.createElement('strong'); val.className = 'sg-report-bar-value'; val.textContent = formatter(v);
            button.append(top, track, val);
            if (onClick) button.addEventListener('click', () => onClick(row));
            host.appendChild(button);
        });
    };

    const showDetail = async (kind, title, extra = {}) => {
        activeDetail = kind;
        const panel = $('sg-report-detail'); panel.hidden = false;
        $('sg-report-detail-title').textContent = title;
        $('sg-report-detail-body').replaceChildren();
        $('sg-report-detail-count').textContent = 'Kayıtlar yükleniyor…';
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        try {
            const data = await api(drillEndpoint + '?' + paramsFor({ kind, ...extra }).toString());
            const items = data.items || [];
            const head = $('sg-report-detail-head'); const body = $('sg-report-detail-body');
            head.replaceChildren(); body.replaceChildren();
            if (!items.length) {
                const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 1; td.className = 'sg-table-empty'; td.textContent = 'Bu filtreyle eşleşen kayıt bulunamadı.'; tr.appendChild(td); body.appendChild(tr);
            } else {
                const columns = Object.keys(items[0]).slice(0, 10);
                const hr = document.createElement('tr'); columns.forEach((key) => { const th = document.createElement('th'); th.textContent = key.replace(/_/g, ' '); hr.appendChild(th); }); head.appendChild(hr);
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    columns.forEach((key) => { const td = document.createElement('td'); const v = item[key]; td.textContent = v == null || v === '' ? '—' : (typeof v === 'number' && /amount|balance|debit|credit/i.test(key) ? money(v) : String(v)); tr.appendChild(td); });
                    body.appendChild(tr);
                });
            }
            $('sg-report-detail-count').textContent = number(data.count) + ' kayıt · En fazla 2.000 satır gösterilir.';
        } catch (error) { $('sg-report-detail-count').textContent = error.message; }
    };

    const render = (data) => {
        report = data;
        const k = data.kpis || {}, c = data.counts || {};
        setText('sg-r-income', money(k.income)); setText('sg-r-expense', money(k.expense)); setText('sg-r-profit', money(k.profit));
        setText('sg-r-margin', 'Kârlılık: ' + (k.profitability == null ? '—' : '%' + number(k.profitability)));
        setText('sg-r-collected', money(k.collected)); setText('sg-r-receivable', money(k.receivable)); setText('sg-r-payable', money(k.payable));
        setText('sg-r-cash', money(k.cash_balance)); setText('sg-r-bank', money(k.bank_balance)); setText('sg-r-paid', money(k.paid)); setText('sg-r-opened', number(c.jobs_opened));
        const categories = data.categories || [];
        const incomeCats = categories.filter((x) => x.record_type === 'income');
        const expenseCats = categories.filter((x) => x.record_type === 'expense');
        renderBars('sg-report-categories', categories.slice(0, 8), (x) => (x.record_type === 'income' ? 'Gelir · ' : 'Gider · ') + x.category_name, (x) => x.amount, (x) => showDetail(x.record_type, x.category_name, { category_id: x.category_id }));
        renderBars('sg-report-income-cats', incomeCats, (x) => x.category_name, (x) => x.amount, (x) => showDetail('income', x.category_name, { category_id: x.category_id }));
        renderBars('sg-report-expense-cats', expenseCats, (x) => x.category_name, (x) => x.amount, (x) => showDetail('expense', x.category_name, { category_id: x.category_id }));
        const trend = data.trend || [];
        const trendRows = trend.flatMap((x) => [{ period: x.period, type: 'Gelir', value: x.income }, { period: x.period, type: 'Gider', value: x.expense }]);
        renderBars('sg-report-trend', trendRows, (x) => x.period + ' · ' + x.type, (x) => x.value, (x) => {
            let start; let end;
            if (data.trend_granularity === 'daily') {
                start = end = parseDate(x.period);
            } else if (data.trend_granularity === 'weekly') {
                const weekCode = Number(x.period); const year = Math.floor(weekCode / 100); const week = weekCode % 100;
                const jan4 = new Date(year, 0, 4, 12); const monday = new Date(jan4); monday.setDate(jan4.getDate() - ((jan4.getDay() + 6) % 7) + (week - 1) * 7);
                start = monday; end = new Date(monday); end.setDate(monday.getDate() + 6);
            } else {
                const month = x.period.split('-').map(Number); start = new Date(month[0], month[1] - 1, 1, 12); end = new Date(month[0], month[1], 0, 12);
            }
            $('sg-report-from').value = localDate(start); $('sg-report-to').value = localDate(end);
            showDetail(x.type === 'Gelir' ? 'income' : 'expense', x.period + ' · ' + x.type);
        });
        const statuses = data.job_statuses || [];
        const statusNames = { new: 'Yeni', assigned: 'Atandı', in_progress: 'Devam ediyor', on_hold: 'Beklemede', completed: 'Tamamlandı', cancelled: 'İptal' };
        renderBars('sg-report-statuses', statuses, (x) => statusNames[x.status] || x.status, (x) => x.count, (x) => showDetail('jobs', statusNames[x.status] || x.status, { status: x.status }), number);
        const staffChartRows = (data.personnel || []).filter((x) => Number(x.completed) + Number(x.ongoing) > 0).slice(0, 30).flatMap((x) => [
            { personnel_id: x.personnel_id, full_name: x.full_name, type: 'Tamamlanan', value: Number(x.completed) },
            { personnel_id: x.personnel_id, full_name: x.full_name, type: 'Açık iş yükü', value: Number(x.ongoing) }
        ]);
        const staffLabel = (x) => x.full_name + ' · ' + x.type;
        const staffClick = (x) => showDetail('personnel', x.full_name + ' · Görev dökümü', { personnel_id: x.personnel_id });
        renderBars('sg-report-staff', staffChartRows.slice(0, 16), staffLabel, (x) => x.value, staffClick, number);
        renderBars('sg-report-profit-trend', trend, (x) => x.period, (x) => Number(x.income) - Number(x.expense), null);
        const comparison = data.comparison;
        const comp = $('sg-report-profit-compare'); comp.replaceChildren();
        if (comparison) {
            [['Önceki dönem geliri', money(comparison.income)], ['Önceki dönem gideri', money(comparison.expense)], ['Önceki dönem net sonucu', money(comparison.profit)]].forEach(([label, val]) => {
                const row = document.createElement('div'); row.className = 'sg-report-comparison-item'; const span = document.createElement('span'); span.textContent = label; const strong = document.createElement('strong'); strong.textContent = val; row.append(span, strong); comp.appendChild(row);
            });
        } else { const p = document.createElement('p'); p.textContent = 'Karşılaştırma kapalı.'; comp.appendChild(p); }
        renderBars('sg-report-cash-accounts', data.cash_accounts || [], (x) => x.account_name + (x.account_kind === 'bank' ? ' · Banka' : ' · Kasa'), (x) => x.balance, (x) => showDetail('cash', x.account_name, { cash_account_id: x.id }));
        const cashTrendRows = (data.cash_trend || []).flatMap((x) => [{ period: x.period, type: 'Tahsilat', value: x.collected }, { period: x.period, type: 'Ödeme', value: x.paid }]);
        renderBars('sg-report-cash-trend', cashTrendRows, (x) => x.period + ' · ' + x.type, (x) => x.value, (x) => {
            let start; let end;
            if (data.trend_granularity === 'daily') {
                start = end = parseDate(x.period);
            } else if (data.trend_granularity === 'weekly') {
                const weekCode = Number(x.period); const year = Math.floor(weekCode / 100); const week = weekCode % 100;
                const jan4 = new Date(year, 0, 4, 12); const monday = new Date(jan4); monday.setDate(jan4.getDate() - ((jan4.getDay() + 6) % 7) + (week - 1) * 7);
                start = monday; end = new Date(monday); end.setDate(monday.getDate() + 6);
            } else {
                const month = String(x.period).split('-').map(Number); start = new Date(month[0], month[1] - 1, 1, 12); end = new Date(month[0], month[1], 0, 12);
            }
            $('sg-report-from').value = localDate(start); $('sg-report-to').value = localDate(end);
            showDetail(x.type === 'Tahsilat' ? 'collection' : 'payment', x.period + ' · ' + x.type);
        });
        renderBars('sg-report-customers', (data.customers || []).slice(0, 12), (x) => x.name, (x) => x.balance, (x) => showDetail('party', x.name + ' · Cari Ekstre', { party_id: x.party_id }));
        renderBars('sg-report-suppliers', (data.suppliers || []).slice(0, 12), (x) => x.name, (x) => x.balance, (x) => showDetail('party', x.name + ' · Cari Ekstre', { party_id: x.party_id }));
        const op = $('sg-report-operations'); op.replaceChildren();
        [['Açılan işler', c.jobs_opened], ['Tamamlanan işler', c.jobs_completed], ['Devam eden işler', c.jobs_ongoing], ['Geciken işler', c.jobs_overdue], ['İptal edilen işler', c.jobs_cancelled], ['Ortalama tamamlanma süresi', number(c.average_completion_days) + ' gün']].forEach(([label, val]) => {
            const row = document.createElement('div'); row.className = 'sg-report-comparison-item'; const span = document.createElement('span'); span.textContent = label; const strong = document.createElement('strong'); strong.textContent = val; row.append(span, strong); op.appendChild(row);
        });
        renderBars('sg-report-personnel', staffChartRows, staffLabel, (x) => x.value, staffClick, number);
        const limits = $('sg-report-limitations'); limits.replaceChildren();
        Object.values(data.limitations || {}).forEach((message) => { const p = document.createElement('p'); p.textContent = message; limits.appendChild(p); });
        const cmp = $('sg-report-compare').checked && comparison;
        if (cmp) {
            const diff = (current, previous) => Number(previous) === 0 ? '—' : (Number(current) - Number(previous) >= 0 ? '+' : '') + number((Number(current) - Number(previous)) / Math.abs(Number(previous)) * 100) + '%';
            setText('sg-r-income', money(k.income) + ' · ' + diff(k.income, comparison.income));
            setText('sg-r-expense', money(k.expense) + ' · ' + diff(k.expense, comparison.expense));
        }
    };

    const load = async () => {
        const button = $('sg-report-apply'); button.disabled = true;
        $('sg-report-message').textContent = 'Raporlar gerçek kayıtlar üzerinden hesaplanıyor…';
        try {
            const data = await api(endpoint + '?' + paramsFor(comparisonParams()).toString());
            render(data);
            $('sg-report-message').textContent = 'Rapor güncellendi · ' + dateText(data.period.from) + ' – ' + dateText(data.period.to);
        } catch (error) { $('sg-report-message').textContent = error.message; }
        finally { button.disabled = false; }
    };

    const activateTab = (tab) => {
        activeTab = tab;
        root.querySelectorAll('[data-report-tab]').forEach((b) => b.classList.toggle('is-active', b.dataset.reportTab === tab));
        root.querySelectorAll('[data-report-panel]').forEach((p) => { p.hidden = p.dataset.reportPanel !== tab; });
        $('sg-report-detail').hidden = true;
    };

    const printPreview = () => {
        if (!report) return;
        const k = report.kpis || {}, c = report.counts || {};
        const kpis = [['Toplam gelir', money(k.income)], ['Toplam gider', money(k.expense)], ['Net kâr / zarar', money(k.profit)], ['Kârlılık oranı', k.profitability == null ? '—' : '%' + number(k.profitability)], ['Tahsil edilen', money(k.collected)], ['Bekleyen alacak', money(k.receivable)], ['Toplam borç', money(k.payable)], ['Kasa bakiyesi', money(k.cash_balance)], ['Banka bakiyesi', money(k.bank_balance)], ['Ödemeler', money(k.paid)]];
        const categoryMax = Math.max(1, ...(report.categories || []).map((x) => Number(x.amount || 0)));
        const categoryRows = (report.categories || []).map((x) => '<tr><td>' + esc(x.record_type === 'income' ? 'Gelir' : 'Gider') + '</td><td>' + esc(x.category_name) + '<div style="height:5px;background:#0878c9;width:' + Math.max(1, Number(x.amount || 0) / categoryMax * 100) + '%;margin-top:4px"></div></td><td class="num">' + esc(money(x.amount)) + '</td><td class="num">' + esc(x.record_count) + '</td></tr>').join('');
        const cashTrendRows = (report.cash_trend || []).map((x) => '<tr><td>' + esc(x.period) + '</td><td class="num">' + esc(money(x.collected)) + '</td><td class="num">' + esc(money(x.paid)) + '</td></tr>').join('');
        const jobRows = (report.job_statuses || []).map((x) => '<tr><td>' + esc(x.status) + '</td><td class="num">' + esc(x.count) + '</td></tr>').join('');
        const trendMax = Math.max(1, ...(report.trend || []).flatMap((x) => [Number(x.income || 0), Number(x.expense || 0)]));
        const trendRows = (report.trend || []).map((x) => '<tr><td>' + esc(x.period) + '</td><td>' + esc(money(x.income)) + '<div style="height:5px;background:#0878c9;width:' + Math.max(1, Number(x.income || 0) / trendMax * 100) + '%;margin-top:4px"></div></td><td>' + esc(money(x.expense)) + '<div style="height:5px;background:#b96b45;width:' + Math.max(1, Number(x.expense || 0) / trendMax * 100) + '%;margin-top:4px"></div></td><td class="num">' + esc(money(Number(x.income)-Number(x.expense))) + '</td></tr>').join('');
        const staffRows = (report.personnel || []).map((x) => '<tr><td>' + esc(x.full_name) + '</td><td class="num">' + esc(x.completed) + '</td><td class="num">' + esc(x.ongoing) + '</td></tr>').join('');
        const logo = SG_APP.logoUrl ? '<img src="' + esc(SG_APP.logoUrl) + '" alt="Sürmeli Grup">' : '<strong>SÜRMELİ GRUP</strong>';
        const html = '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Sürmeli Grup · Yönetim Raporu</title><style>@page{size:A4;margin:14mm}*{box-sizing:border-box}body{font:10pt Arial,sans-serif;color:#1e293b;margin:0}.toolbar{position:sticky;top:0;padding:12px;background:#f1f5f9;text-align:right}.toolbar button{padding:9px 15px;border:0;border-radius:6px;background:#0878c9;color:#fff;font-weight:bold;cursor:pointer}.sheet{max-width:190mm;margin:18px auto;background:#fff}.head{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #0878c9;padding-bottom:12px}.head img{max-width:180px;max-height:55px;object-fit:contain}.muted{color:#64748b;font-size:9pt}.kpis{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:18px 0}.kpi{border:1px solid #dce3eb;border-radius:5px;padding:9px}.kpi span{display:block;color:#64748b;font-size:8pt}.kpi strong{display:block;margin-top:5px;font-size:12pt}h2{font-size:13pt;margin:20px 0 8px}table{width:100%;border-collapse:collapse;font-size:9pt;page-break-inside:auto}thead{display:table-header-group}tr{break-inside:avoid}th,td{padding:7px;border:1px solid #dce3eb;text-align:left}th{background:#f1f5f9}.num{text-align:right}footer{margin-top:22px;padding-top:8px;border-top:1px solid #dce3eb;color:#64748b;font-size:8pt;display:flex;justify-content:space-between}.pageNumber:after{content:counter(page)}.note{font-size:8pt;color:#64748b;line-height:1.5}@media print{.toolbar{display:none}.sheet{margin:0;max-width:none}body{print-color-adjust:exact;-webkit-print-color-adjust:exact}}</style></head><body><div class="toolbar"><button onclick="window.print()">Yazdır / PDF olarak kaydet</button></div><main class="sheet"><header class="head"><div>' + logo + '<h1>Yönetim Raporu</h1><div class="muted">Rapor dönemi: ' + esc(dateText(report.period.from)) + ' – ' + esc(dateText(report.period.to)) + '</div></div><div class="muted">Oluşturulma: ' + esc(report.generated_at) + '</div></header><section class="kpis">' + kpis.map((x) => '<div class="kpi"><span>' + esc(x[0]) + '</span><strong>' + esc(x[1]) + '</strong></div>').join('') + '</section><h2>Dönemsel gelir-gider grafiği</h2><table><thead><tr><th>Dönem</th><th>Gelir</th><th>Gider</th><th>Net</th></tr></thead><tbody>' + (trendRows || '<tr><td colspan="4">Kayıt bulunamadı</td></tr>') + '</tbody></table><h2>Finansal kategori dökümü</h2><table><thead><tr><th>Tür</th><th>Kategori</th><th class="num">Tutar</th><th class="num">Kayıt</th></tr></thead><tbody>' + (categoryRows || '<tr><td colspan="4">Kayıt bulunamadı</td></tr>') + '</tbody></table><h2>Tahsilat ve ödeme</h2><table><thead><tr><th>Dönem</th><th>Tahsilat</th><th>Ödeme</th></tr></thead><tbody>' + (cashTrendRows || '<tr><td colspan="3">Kayıt bulunamadı</td></tr>') + '</tbody></table><h2>Operasyon özeti</h2><p>Açılan: ' + esc(c.jobs_opened) + ' · Tamamlanan: ' + esc(c.jobs_completed) + ' · Devam eden: ' + esc(c.jobs_ongoing) + ' · Geciken: ' + esc(c.jobs_overdue) + ' · İptal: ' + esc(c.jobs_cancelled) + ' · Ortalama tamamlanma: ' + esc(number(c.average_completion_days)) + ' gün</p><table><thead><tr><th>İş durumu</th><th>Adet</th></tr></thead><tbody>' + (jobRows || '<tr><td colspan="2">Kayıt bulunamadı</td></tr>') + '</tbody></table><h2>Personel performansı</h2><table><thead><tr><th>Personel</th><th>Tamamlanan</th><th>Devam eden</th></tr></thead><tbody>' + (staffRows || '<tr><td colspan="3">Kayıt bulunamadı</td></tr>') + '</tbody></table><p class="note">Rapor yalnızca mevcut Sürmeli Grup veritabanındaki muhasebeleştirilmiş kayıtları içerir. Vade tarihi ve ayrı gelir kaynağı alanları mevcut olmadığından ilgili analizler hesaplanmamıştır.</p><footer><span>Sürmeli Grup · İşletme Yönetim Sistemi</span><span>Sayfa <span class="pageNumber"></span></span></footer></main></body></html>';
        const win = window.open('', '_blank');
        if (!win) { $('sg-report-message').textContent = 'Önizleme penceresi engellendi. Tarayıcıda açılır pencerelere izin verin.'; return; }
        win.document.open(); win.document.write(html); win.document.close();
    };

    const downloadCsv = async () => {
        if (!report) return;
        const map = { overview: 'summary', income: 'income', expense: 'expense', profit: 'summary', cash: 'cash', accounts: 'accounts', operations: 'jobs', personnel: 'personnel' };
        const kind = map[activeTab] || 'summary';
        const button = $('sg-report-csv'); button.disabled = true;
        try {
            const data = await api(exportEndpoint + '?' + paramsFor({ kind }).toString());
            const blob = new Blob([data.csv || ''], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob); const a = document.createElement('a');
            a.href = url; a.download = data.filename || 'surmeli-grup-rapor.csv'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
        } catch (error) { $('sg-report-message').textContent = error.message; }
        finally { button.disabled = false; }
    };

    const init = async () => {
        try {
            const permissions = await api(SG_APP.permissionsEndpoint);
            const granted = new Set(permissions.permissions || []);
            if (!granted.has('reports.view')) return;
            if (!granted.has('personnel.view')) {
                const personnelTab = root.querySelector('[data-report-tab="personnel"]');
                const personnelPanel = root.querySelector('[data-report-panel="personnel"]');
                if (personnelTab) personnelTab.hidden = true;
                if (personnelPanel) personnelPanel.hidden = true;
            }
            const preset = $('sg-report-preset');
            setPeriod('month');
            preset.addEventListener('change', () => { if (preset.value !== 'custom') setPeriod(preset.value); });
            $('sg-report-apply').addEventListener('click', load);
            $('sg-report-from').addEventListener('change', () => { preset.value = 'custom'; });
            $('sg-report-to').addEventListener('change', () => { preset.value = 'custom'; });
            $('sg-report-compare').addEventListener('change', load);
            root.querySelectorAll('[data-report-tab]').forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.reportTab)));
            $('sg-report-detail-close').addEventListener('click', () => { $('sg-report-detail').hidden = true; });
            $('sg-report-pdf').addEventListener('click', printPreview);
            $('sg-report-csv').addEventListener('click', downloadCsv);
            load();
        } catch (error) { $('sg-report-message').textContent = error.message; }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();