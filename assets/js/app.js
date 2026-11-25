(() => {
    'use strict';
    const root = document.getElementById('sg-app-root');
    if (!root) return;

    const toggle = root.querySelector('.sg-menu-toggle');
    const sidebar = root.querySelector('.sg-sidebar');
    const userName = root.querySelector('#sg-user-name');
    const sessionState = root.querySelector('#sg-session-state');
    const apiState = root.querySelector('#sg-api-state');

    const dashboardBody = root.querySelector('#sg-recent-jobs');
    const dashboardUpdated = root.querySelector('#sg-dashboard-updated');
    const customerRows = root.querySelector('#sg-customer-rows');
    const supplierRows = root.querySelector('#sg-supplier-rows');
    let customerItems = [];
    let supplierItems = [];
    let supplierSearchTimer = null;
    let customerSearchTimer = null;

    const statusLabels = {
        new: 'Yeni', assigned: 'Atandı', in_progress: 'Devam ediyor',
        on_hold: 'Beklemede', completed: 'Tamamlandı', cancelled: 'İptal edildi'
    };

    const formatDate = (value) => {
        if (!value || value === '0000-00-00 00:00:00') return '—';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return '—';
        return new Intl.DateTimeFormat('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
    };

    const loadDashboard = () => {
        if (!SG_APP.dashboardEndpoint) return;
        fetch(SG_APP.dashboardEndpoint, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': SG_APP.nonce, 'Accept': 'application/json' }
        }).then((response) => {
            if (!response.ok) throw new Error('Dashboard verileri alınamadı');
            return response.json();
        }).then((data) => {
            ['customers', 'suppliers', 'jobs_total', 'jobs_open', 'jobs_overdue', 'jobs_completed'].forEach((key) => {
                const target = root.querySelector('[data-stat="' + key + '"]');
                if (target) target.textContent = new Intl.NumberFormat('tr-TR').format(Number(data[key] || 0));
            });
            if (dashboardBody) {
                dashboardBody.replaceChildren();
                if (!Array.isArray(data.recent_jobs) || !data.recent_jobs.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 4;
                    cell.className = 'sg-table-empty';
                    cell.textContent = 'Henüz iş kaydı bulunmuyor.';
                    row.appendChild(cell);
                    dashboardBody.appendChild(row);
                } else {
                    data.recent_jobs.forEach((job) => {
                        const row = document.createElement('tr');
                        [job.job_no, job.title, statusLabels[job.status] || job.status, formatDate(job.due_date)].forEach((value, index) => {
                            const cell = document.createElement('td');
                            cell.textContent = value || '—';
                            if (index === 2) cell.className = 'sg-job-status sg-job-status-' + String(job.status).replace(/[^a-z_]/g, '');
                            row.appendChild(cell);
                        });
                        dashboardBody.appendChild(row);
                    });
                }
            }
            if (dashboardUpdated) dashboardUpdated.textContent = 'Son güncelleme: ' + formatDate(data.generated_at);
        }).catch(() => {
            if (dashboardBody) {
                dashboardBody.replaceChildren();
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 4;
                cell.className = 'sg-table-empty';
                cell.textContent = 'Dashboard verileri şu anda alınamıyor.';
                row.appendChild(cell);
                dashboardBody.appendChild(row);
            }
        });
    };

    const apiRequest = (url, options = {}) => fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: { 'X-WP-Nonce': SG_APP.nonce, 'Accept': 'application/json', ...(options.headers || {}) }
    }).then(async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'İşlem tamamlanamadı.');
        return data;
    });


    const setupDashboardCalendar = (permissions) => {
        const grid=root.querySelector('#sg-dashboard-calendar-grid');
        if(!grid||!permissions.has('jobs.view')||!SG_APP.jobsEndpoint)return;
        let date=new Date(), tasks=[];
        const render=()=>{
            grid.replaceChildren();
            const y=date.getFullYear(),m=date.getMonth();
            const title=root.querySelector('#sg-dashboard-calendar-title');
            if(title)title.textContent=new Intl.DateTimeFormat('tr-TR',{month:'long',year:'numeric'}).format(date);
            ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'].forEach(v=>{const h=document.createElement('div');h.className='sg-calendar-weekday';h.textContent=v;grid.appendChild(h);});
            const first=new Date(y,m,1),start=new Date(y,m,1-((first.getDay()+6)%7));
            for(let i=0;i<42;i++){
                const day=new Date(start);day.setDate(start.getDate()+i);
                const key=[day.getFullYear(),String(day.getMonth()+1).padStart(2,'0'),String(day.getDate()).padStart(2,'0')].join('-');
                const cell=document.createElement('div');cell.className='sg-calendar-day'+(day.getMonth()!==m?' is-muted':'');
                const n=document.createElement('span');n.className='sg-calendar-day-number';n.textContent=day.getDate();cell.appendChild(n);
                tasks.filter(t=>String(t.due_date||'').slice(0,10)===key).slice(0,3).forEach(t=>{const b=document.createElement('button');b.type='button';b.className='sg-calendar-event';b.textContent=t.title;b.title=t.title;b.addEventListener('click',()=>{window.location.hash='jobs';});cell.appendChild(b);});
                grid.appendChild(cell);
            }
        };
        const load=()=>apiRequest(SG_APP.jobsEndpoint).then(d=>{tasks=d.items||[];render();}).catch(()=>{grid.textContent='Görev takvimi şu anda yüklenemiyor.';});
        root.querySelector('#sg-dashboard-calendar-prev').addEventListener('click',()=>{date.setMonth(date.getMonth()-1);render();});
        root.querySelector('#sg-dashboard-calendar-next').addEventListener('click',()=>{date.setMonth(date.getMonth()+1);render();});
        load();
    };

    const setupNavigation = (permissions) => {
        const activate = () => {
            let section = (window.location.hash || '#dashboard').slice(1);
            if (section === 'customers' && !permissions.has('customers.view')) section = 'dashboard';
            if (section === 'suppliers' && !permissions.has('suppliers.view')) section = 'dashboard';
            if (section === 'accounts' && !permissions.has('accounts.view')) section = 'dashboard';
            if (section === 'cash-bank' && !permissions.has('cash.view')) section = 'dashboard';
            if (section === 'income-expense' && !permissions.has('income_expense.view')) section = 'dashboard';
            if (section === 'personnel' && !permissions.has('personnel.view')) section = 'dashboard';
            if (section === 'jobs' && !permissions.has('jobs.view')) section = 'dashboard';
            root.querySelectorAll('[data-section-view]').forEach((view) => { view.hidden = view.dataset.sectionView !== section; });
            root.querySelectorAll('.sg-sidebar [data-section]').forEach((link) => link.classList.toggle('is-active', link.dataset.section === section));
            const heading = root.querySelector('.sg-page-heading');
            if (heading) heading.hidden = section !== 'dashboard';
        };
        root.querySelectorAll('.sg-sidebar [data-section]').forEach((link) => link.addEventListener('click', () => { window.location.hash = link.dataset.section; }));
        window.addEventListener('hashchange', activate);
        activate();
    };

    const formatTurkishPhone = (value) => {
        let digits = String(value || '').replace(/\D/g, '').slice(0, 11);
        if (digits.length && digits[0] !== '0') digits = ('0' + digits).slice(0, 11);
        if (!digits) return '';
        let out = digits.slice(0, 1);
        if (digits.length > 1) out += ' (' + digits.slice(1, 4);
        if (digits.length >= 4) out += ')';
        if (digits.length > 4) out += ' ' + digits.slice(4, 7);
        if (digits.length > 7) out += ' ' + digits.slice(7, 9);
        if (digits.length > 9) out += ' ' + digits.slice(9, 11);
        return out;
    };

    const setupCustomerModule = (permissions) => {
        if (!customerRows || !SG_APP.customersEndpoint || !permissions.has('customers.view')) return;
        const editor = root.querySelector('#sg-customer-editor');
        const form = root.querySelector('#sg-customer-form');
        const message = root.querySelector('#sg-customer-message');
        const phoneField = root.querySelector('#sg-customer-phone');
        phoneField.addEventListener('input', () => { phoneField.value = formatTurkishPhone(phoneField.value); });
        editor.addEventListener('click', (event) => { if (event.target === editor) editor.close(); });
        const fields = {
            party_kind: 'sg-customer-kind', company_name: 'sg-customer-company', contact_name: 'sg-customer-contact',
            phone: 'sg-customer-phone', email: 'sg-customer-email', tax_office: 'sg-customer-tax-office',
            tax_number: 'sg-customer-tax-number', status: 'sg-customer-state', address: 'sg-customer-address', description: 'sg-customer-description'
        };
        const val = (id) => root.querySelector('#' + id);
        const load = () => {
            const params = new URLSearchParams();
            if (val('sg-customer-search').value.trim()) params.set('search', val('sg-customer-search').value.trim());
            if (val('sg-customer-status').value) params.set('status', val('sg-customer-status').value);
            customerRows.innerHTML = '<tr><td colspan="7" class="sg-table-empty">Müşteriler yükleniyor…</td></tr>';
            apiRequest(SG_APP.customersEndpoint + (params.toString() ? '?' + params.toString() : '')).then((data) => {
                customerItems = Array.isArray(data.items) ? data.items : [];
                val('sg-customer-count').textContent = customerItems.length + ' müşteri kaydı';
                customerRows.replaceChildren();
                if (!customerItems.length) {
                    const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 7; td.className = 'sg-table-empty'; td.textContent = 'Aramanızla eşleşen müşteri bulunamadı.'; tr.appendChild(td); customerRows.appendChild(tr); return;
                }
                customerItems.forEach((customer) => {
                    const tr = document.createElement('tr');
                    [['Müşteri Kodu',customer.party_code],['Müşteri / Firma',customer.company_name],['Yetkili',customer.contact_name],['Telefon',customer.phone ? formatTurkishPhone(customer.phone) : ''],['E-posta',customer.email]].forEach(([label,value]) => { const td = document.createElement('td'); td.dataset.label = label; td.textContent = value || '—'; tr.appendChild(td); });
                    const status = document.createElement('td'); status.dataset.label = 'Durum'; status.textContent = customer.status === 'active' ? 'Aktif' : 'Pasif'; status.className = customer.status === 'active' ? 'sg-customer-active' : 'sg-customer-passive'; tr.appendChild(status);
                    const actions = document.createElement('td'); actions.className = 'sg-customer-actions';
                    const view = document.createElement('button'); view.type = 'button'; view.className = 'sg-button sg-button-small sg-button-light'; view.textContent = 'Detay'; view.addEventListener('click', () => openCustomer(customer.id, true)); actions.appendChild(view);
                    if (permissions.has('customers.edit')) { const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'sg-button sg-button-small sg-button-light'; edit.textContent = 'Düzenle'; edit.addEventListener('click', () => openCustomer(customer.id, false)); actions.appendChild(edit); }
                    tr.appendChild(actions); customerRows.appendChild(tr);
                });
            }).catch((error) => { customerRows.replaceChildren(); const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 7; td.className = 'sg-table-empty'; td.textContent = error.message; tr.appendChild(td); customerRows.appendChild(tr); });
        };
        const openCustomer = (id, readOnly) => apiRequest(SG_APP.customersEndpoint + '/' + id).then((customer) => {
            val('sg-customer-id').value = customer.id;
            Object.entries(fields).forEach(([key, fieldId]) => { val(fieldId).value = key === 'phone' && customer[key] ? formatTurkishPhone(customer[key]) : (customer[key] || (key === 'status' ? 'active' : '')); val(fieldId).disabled = readOnly; });
            val('sg-customer-editor-title').textContent = readOnly ? 'Müşteri Detayı' : 'Müşteri Düzenle';
            val('sg-customer-save').hidden = readOnly;
            message.textContent = readOnly ? 'Müşteri bilgileri görüntüleniyor.' : '';
            if (!editor.open) editor.showModal();
        }).catch((error) => { window.alert(error.message); });
        const resetEditor = () => {
            form.reset(); val('sg-customer-id').value = ''; Object.values(fields).forEach((id) => { val(id).disabled = false; });
            val('sg-customer-editor-title').textContent = 'Yeni Müşteri'; val('sg-customer-save').hidden = false; message.textContent = ''; if (editor.open) editor.close();
        };
        if (permissions.has('customers.create')) root.querySelector('#sg-customer-add').addEventListener('click', () => { resetEditor(); if (!editor.open) editor.showModal(); });
        root.querySelector('#sg-customer-cancel').addEventListener('click', resetEditor);
        root.querySelector('#sg-customer-search').addEventListener('input', () => { clearTimeout(customerSearchTimer); customerSearchTimer = setTimeout(load, 250); });
        root.querySelector('#sg-customer-status').addEventListener('change', load);
        form.addEventListener('submit', (event) => {
            event.preventDefault(); if (!permissions.has(val('sg-customer-id').value ? 'customers.edit' : 'customers.create')) return;
            const payload = {}; Object.entries(fields).forEach(([key, fieldId]) => { payload[key] = val(fieldId).value; });
            const id = val('sg-customer-id').value;
            val('sg-customer-save').disabled = true; message.textContent = 'Kaydediliyor…';
            apiRequest(id ? SG_APP.customersEndpoint + '/' + id : SG_APP.customersEndpoint, { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then((result) => {
                message.textContent = result.message || 'Kaydedildi.'; load();
                window.setTimeout(resetEditor, 900);
            }).catch((error) => { message.textContent = error.message; }).finally(() => { val('sg-customer-save').disabled = false; });
        });
        load();
    };


    const setupSupplierModule = (permissions) => {
        if (!supplierRows || !SG_APP.suppliersEndpoint || !permissions.has('suppliers.view')) return;
        const editor = root.querySelector('#sg-supplier-editor');
        const form = root.querySelector('#sg-supplier-form');
        const message = root.querySelector('#sg-supplier-message');
        const phoneField = root.querySelector('#sg-supplier-phone');
        phoneField.addEventListener('input', () => { phoneField.value = formatTurkishPhone(phoneField.value); });
        editor.addEventListener('click', (event) => { if (event.target === editor) editor.close(); });
        const fields = {
            party_kind: 'sg-supplier-kind', company_name: 'sg-supplier-company', contact_name: 'sg-supplier-contact',
            phone: 'sg-supplier-phone', email: 'sg-supplier-email', tax_office: 'sg-supplier-tax-office',
            tax_number: 'sg-supplier-tax-number', status: 'sg-supplier-state', address: 'sg-supplier-address', description: 'sg-supplier-description'
        };
        const val = (id) => root.querySelector('#' + id);
        const load = () => {
            const params = new URLSearchParams();
            if (val('sg-supplier-search').value.trim()) params.set('search', val('sg-supplier-search').value.trim());
            if (val('sg-supplier-status').value) params.set('status', val('sg-supplier-status').value);
            supplierRows.innerHTML = '<tr><td colspan="7" class="sg-table-empty">Tedarikçiler yükleniyor…</td></tr>';
            apiRequest(SG_APP.suppliersEndpoint + (params.toString() ? '?' + params.toString() : '')).then((data) => {
                supplierItems = Array.isArray(data.items) ? data.items : [];
                val('sg-supplier-count').textContent = supplierItems.length + ' tedarikçi kaydı';
                supplierRows.replaceChildren();
                if (!supplierItems.length) {
                    const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 7; td.className = 'sg-table-empty'; td.textContent = 'Aramanızla eşleşen tedarikçi bulunamadı.'; tr.appendChild(td); supplierRows.appendChild(tr); return;
                }
                supplierItems.forEach((supplier) => {
                    const tr = document.createElement('tr');
                    [supplier.party_code, supplier.company_name, supplier.contact_name, supplier.phone ? formatTurkishPhone(supplier.phone) : '', supplier.email].forEach((value) => { const td = document.createElement('td'); td.textContent = value || '—'; tr.appendChild(td); });
                    const status = document.createElement('td'); status.textContent = supplier.status === 'active' ? 'Aktif' : 'Pasif'; status.className = supplier.status === 'active' ? 'sg-supplier-active' : 'sg-supplier-passive'; tr.appendChild(status);
                    const actions = document.createElement('td'); actions.className = 'sg-supplier-actions';
                    const view = document.createElement('button'); view.type = 'button'; view.className = 'sg-button sg-button-small sg-button-light'; view.textContent = 'Detay'; view.addEventListener('click', () => openSupplier(supplier.id, true)); actions.appendChild(view);
                    if (permissions.has('suppliers.edit')) { const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'sg-button sg-button-small sg-button-light'; edit.textContent = 'Düzenle'; edit.addEventListener('click', () => openSupplier(supplier.id, false)); actions.appendChild(edit); }
                    tr.appendChild(actions); supplierRows.appendChild(tr);
                });
            }).catch((error) => { supplierRows.replaceChildren(); const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 7; td.className = 'sg-table-empty'; td.textContent = error.message; tr.appendChild(td); supplierRows.appendChild(tr); });
        };
        const openSupplier = (id, readOnly) => apiRequest(SG_APP.suppliersEndpoint + '/' + id).then((supplier) => {
            val('sg-supplier-id').value = supplier.id;
            Object.entries(fields).forEach(([key, fieldId]) => { val(fieldId).value = key === 'phone' && supplier[key] ? formatTurkishPhone(supplier[key]) : (supplier[key] || (key === 'status' ? 'active' : '')); val(fieldId).disabled = readOnly; });
            val('sg-supplier-editor-title').textContent = readOnly ? 'Tedarikçi Detayı' : 'Tedarikçi Düzenle';
            val('sg-supplier-save').hidden = readOnly;
            message.textContent = readOnly ? 'Tedarikçi bilgileri görüntüleniyor.' : '';
            if (!editor.open) editor.showModal();
        }).catch((error) => { window.alert(error.message); });
        const resetEditor = () => {
            form.reset(); val('sg-supplier-id').value = ''; Object.values(fields).forEach((id) => { val(id).disabled = false; });
            val('sg-supplier-editor-title').textContent = 'Yeni Tedarikçi'; val('sg-supplier-save').hidden = false; message.textContent = ''; if (editor.open) editor.close();
        };
        if (permissions.has('suppliers.create')) root.querySelector('#sg-supplier-add').addEventListener('click', () => { resetEditor(); if (!editor.open) editor.showModal(); });
        root.querySelector('#sg-supplier-cancel').addEventListener('click', resetEditor);
        root.querySelector('#sg-supplier-search').addEventListener('input', () => { clearTimeout(supplierSearchTimer); supplierSearchTimer = setTimeout(load, 250); });
        root.querySelector('#sg-supplier-status').addEventListener('change', load);
        form.addEventListener('submit', (event) => {
            event.preventDefault(); if (!permissions.has(val('sg-supplier-id').value ? 'suppliers.edit' : 'suppliers.create')) return;
            const payload = {}; Object.entries(fields).forEach(([key, fieldId]) => { payload[key] = val(fieldId).value; });
            const id = val('sg-supplier-id').value;
            val('sg-supplier-save').disabled = true; message.textContent = 'Kaydediliyor…';
            apiRequest(id ? SG_APP.suppliersEndpoint + '/' + id : SG_APP.suppliersEndpoint, { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then((result) => {
                message.textContent = result.message || 'Kaydedildi.'; load();
                window.setTimeout(resetEditor, 900);
            }).catch((error) => { message.textContent = error.message; }).finally(() => { val('sg-supplier-save').disabled = false; });
        });
        load();
    };

    const setupAccountsModule = (permissions) => {
        if (!permissions.has('accounts.view') || !SG_APP.accountsEndpoint) return;
        const val = (id) => root.querySelector('#' + id);
        const rows = root.querySelector('#sg-account-rows');
        const dialog = root.querySelector('#sg-account-dialog');
        const tbody = root.querySelector('#sg-account-movements');
        const money = (v) => new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(Number(v||0));
        const load = () => {
            const params = new URLSearchParams();
            const q = val('sg-account-search').value.trim(); const type = val('sg-account-type').value;
            if(q) params.set('search',q); if(type) params.set('type',type);
            apiRequest(SG_APP.accountsEndpoint + (params.toString()?'?'+params.toString():'' )).then(data=>{
                rows.replaceChildren(); val('sg-account-count').textContent=(data.count||0)+' cari';
                if(!data.items || !data.items.length){ const tr=document.createElement('tr'); const td=document.createElement('td'); td.colSpan=7; td.className='sg-table-empty'; td.textContent='Cari kayıt bulunamadı.'; tr.appendChild(td); rows.appendChild(tr); return; }
                data.items.forEach(item=>{ const tr=document.createElement('tr');
                    [item.party_code,item.company_name,item.party_type==='customer'?'Müşteri':'Tedarikçi',money(item.total_debit),money(item.total_credit),money(item.balance)].forEach((v,i)=>{const td=document.createElement('td');td.textContent=v||'—';if(i===5)td.className='sg-account-balance';tr.appendChild(td);});
                    const td=document.createElement('td');const btn=document.createElement('button');btn.type='button';btn.className='sg-button sg-button-light';btn.textContent='Hesabı Aç';btn.addEventListener('click',()=>openParty(item));td.appendChild(btn);tr.appendChild(td);rows.appendChild(tr);
                });
            }).catch(e=>{rows.textContent=e.message;});
        };
        const openParty = (item) => {
            val('sg-account-party-id').value=item.party_id; val('sg-account-party-title').textContent=item.company_name+' · '+item.party_code;
            val('sg-account-summary').textContent='Toplam borç: '+money(item.total_debit)+' | Toplam alacak: '+money(item.total_credit)+' | Bakiye: '+money(item.balance);
            val('sg-account-date').value=new Date().toISOString().slice(0,10); val('sg-account-message').textContent='';
            const save=val('sg-account-save'); save.hidden=!permissions.has('accounts.adjust');
            if(!dialog.open)dialog.showModal();
            apiRequest(SG_APP.accountsEndpoint+'/'+item.party_id+'/movements').then(data=>{tbody.replaceChildren();
                if(!data.items.length){const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=5;td.className='sg-table-empty';td.textContent='Henüz cari hareket yok.';tr.appendChild(td);tbody.appendChild(tr);return;}
                data.items.forEach(m=>{const tr=document.createElement('tr');[formatDate(m.entry_date),m.entry_no,m.description||m.entry_type,money(m.debit),money(m.credit)].forEach(v=>{const td=document.createElement('td');td.textContent=v||'—';tr.appendChild(td);});tbody.appendChild(tr);});
            }).catch(e=>{tbody.textContent=e.message;});
        };
        val('sg-account-close').addEventListener('click',()=>dialog.close());
        dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close();});
        let timer; val('sg-account-search').addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(load,250);});val('sg-account-type').addEventListener('change',load);
        val('sg-account-movement-form').addEventListener('submit',e=>{
            e.preventDefault(); if(!permissions.has('accounts.adjust'))return;
            const payload={party_id:Number(val('sg-account-party-id').value),movement_type:val('sg-account-movement-type').value,amount:val('sg-account-amount').value.trim().replace(',','.'),entry_date:val('sg-account-date').value,document_no:val('sg-account-document').value,description:val('sg-account-description').value};
            val('sg-account-save').disabled=true;val('sg-account-message').textContent='Kaydediliyor…';
            apiRequest(SG_APP.accountsEndpoint,{method:'POST',headers:{'Content-Type':'application/json','Idempotency-Key':crypto.randomUUID()},body:JSON.stringify(payload)}).then(result=>{val('sg-account-message').textContent=result.message;val('sg-account-amount').value='';val('sg-account-description').value='';load();
                apiRequest(SG_APP.accountsEndpoint+'/'+payload.party_id+'/movements').then(data=>{tbody.replaceChildren();data.items.forEach(m=>{const tr=document.createElement('tr');[formatDate(m.entry_date),m.entry_no,m.description||m.entry_type,money(m.debit),money(m.credit)].forEach(v=>{const td=document.createElement('td');td.textContent=v||'—';tr.appendChild(td);});tbody.appendChild(tr);});});
            }).catch(err=>val('sg-account-message').textContent=err.message).finally(()=>val('sg-account-save').disabled=false);
        });
        load();
    };

    const setupCashBankModule = (permissions) => {
        if (!permissions.has('cash.view') || !SG_APP.cashAccountsEndpoint) return;
        const val = (id) => root.querySelector('#' + id);
        const cards = val('sg-cash-cards');
        const accountDialog = val('sg-cash-account-dialog');
        const detailDialog = val('sg-cash-detail-dialog');
        const transferDialog = val('sg-cash-transfer-dialog');
        const money = (v) => new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(Number(v || 0));
        let cashItems = [];
        let partyItems = [];
        let activeCash = null;
        const today = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
        const fillSelect = (select, items, placeholder) => {
            select.replaceChildren();
            const first = document.createElement('option'); first.value = ''; first.textContent = placeholder; select.appendChild(first);
            items.forEach(item => { const o = document.createElement('option'); o.value = item.id; o.textContent = item.account_name + ' · ' + money(item.balance); select.appendChild(o); });
        };
        const load = () => apiRequest(SG_APP.cashAccountsEndpoint).then(data => {
            cashItems = data.items || []; cards.replaceChildren();
            const transferAdd = val('sg-transfer-add'); const transferHint = val('sg-transfer-availability'); if (transferAdd) { const unavailable = cashItems.length < 2; transferAdd.disabled = unavailable; transferAdd.setAttribute('aria-disabled', unavailable ? 'true' : 'false'); transferAdd.title = unavailable ? 'Transfer için en az iki aktif kasa veya banka hesabı gerekir.' : ''; if (transferHint) { transferHint.textContent = unavailable ? 'Transfer işlemini açmak için önce en az iki aktif kasa veya banka hesabı oluşturun.' : ''; transferHint.hidden = !unavailable; } }
            if (!cashItems.length) { const p = document.createElement('div'); p.className = 'sg-panel sg-cash-empty'; p.textContent = 'Henüz kasa veya banka hesabı oluşturulmadı. İlk hesabınızı ekleyerek başlayın.'; cards.appendChild(p); }
            cashItems.forEach(item => {
                const card = document.createElement('article'); card.className = 'sg-cash-card';
                const top = document.createElement('div'); top.className = 'sg-cash-card-top';
                const type = document.createElement('span'); type.className = 'sg-cash-type'; type.textContent = item.account_kind === 'bank' ? 'BANKA' : 'KASA';
                const code = document.createElement('small'); code.textContent = item.account_code;
                top.append(type, code);
                const name = document.createElement('h2'); name.textContent = item.account_name;
                const bank = document.createElement('p'); bank.textContent = item.bank_name || (item.iban ? item.iban : (item.account_kind === 'cash' ? 'Nakit hesabı' : 'Banka hesabı'));
                const balance = document.createElement('strong'); balance.textContent = money(item.balance);
                const label = document.createElement('small'); label.textContent = 'Güncel bakiye';
                const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sg-button sg-button-light'; btn.textContent = 'Hesap Detayı / İşlem'; btn.addEventListener('click', () => openDetail(item));
                card.append(top, name, bank, label, balance, btn); cards.appendChild(card);
            });
            fillSelect(val('sg-transfer-from'), cashItems, 'Kaynak hesap seçin'); fillSelect(val('sg-transfer-to'), cashItems, 'Hedef hesap seçin');
        }).catch(e => { cards.textContent = e.message; });
        const loadParties = () => { if (!permissions.has('cash.create') || !SG_APP.cashPartiesEndpoint) return; apiRequest(SG_APP.cashPartiesEndpoint).then(d => { partyItems = d.items || []; updatePartyOptions(); }).catch(() => {}); };
        const updatePartyOptions = () => {
            const type = val('sg-cash-transaction-type').value;
            const wanted = type === 'collection' ? 'customer' : 'supplier';
            const select = val('sg-cash-party'); select.replaceChildren();
            const first = document.createElement('option'); first.value = ''; first.textContent = type === 'collection' ? 'Müşteri seçin' : 'Tedarikçi seçin'; select.appendChild(first);
            partyItems.filter(p => p.party_type === wanted).forEach(p => { const o = document.createElement('option'); o.value = p.party_id; o.textContent = p.company_name + ' · ' + p.party_code; select.appendChild(o); });
            select.disabled = !['collection', 'payment'].includes(type);
            if (select.disabled) { const o = document.createElement('option'); o.value = ''; o.textContent = 'Bu işlem için cari seçilmez'; select.appendChild(o); }
        };
        const openDetail = (item) => {
            activeCash = item; val('sg-cash-detail-title').textContent = item.account_name + ' · ' + item.account_code;
            val('sg-cash-detail-summary').textContent = 'Güncel bakiye: ' + money(item.balance);
            val('sg-cash-transaction-date').value = today(); val('sg-cash-transaction-message').textContent = '';
            val('sg-cash-transaction-form').querySelector('button[type="submit"]').hidden = !permissions.has('cash.create');
            if (!detailDialog.open) detailDialog.showModal();
            apiRequest(SG_APP.cashAccountsEndpoint + '/' + item.id + '/transactions').then(data => {
                const tbody = val('sg-cash-transactions'); tbody.replaceChildren();
                if (!data.items.length) { const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 5; td.className = 'sg-table-empty'; td.textContent = 'Henüz hareket bulunmuyor.'; tr.appendChild(td); tbody.appendChild(tr); return; }
                data.items.forEach(m => { const tr = document.createElement('tr'); [formatDate(m.transaction_date), m.entry_no, (m.company_name ? m.company_name + ' · ' : '') + (m.description || m.entry_type), m.direction === 'in' ? money(m.amount) : '—', m.direction === 'out' ? money(m.amount) : '—'].forEach(v => { const td = document.createElement('td'); td.textContent = v || '—'; tr.appendChild(td); }); tbody.appendChild(tr); });
            }).catch(e => { val('sg-cash-transactions').textContent = e.message; });
        };
        val('sg-cash-add').addEventListener('click', () => { val('sg-cash-account-form').reset(); val('sg-cash-account-message').textContent = ''; val('sg-cash-opening-date').value = today(); accountDialog.showModal(); });
        val('sg-cash-account-close').addEventListener('click', () => accountDialog.close());
        val('sg-cash-detail-close').addEventListener('click', () => detailDialog.close());
        [accountDialog, detailDialog].forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));
        val('sg-cash-kind').addEventListener('change', () => { const bank = val('sg-cash-kind').value === 'bank'; val('sg-cash-bank').disabled = !bank; val('sg-cash-iban').disabled = !bank; });
        val('sg-cash-account-form').addEventListener('submit', e => {
            e.preventDefault(); const b = e.currentTarget.querySelector('button[type="submit"]'); b.disabled = true; val('sg-cash-account-message').textContent = 'Kaydediliyor…';
            const payload = { account_kind: val('sg-cash-kind').value, account_name: val('sg-cash-name').value.trim(), bank_name: val('sg-cash-bank').value.trim(), iban: val('sg-cash-iban').value.trim(), opening_balance: val('sg-cash-opening').value.trim().replace(',', '.'), opening_date: val('sg-cash-opening-date').value };
            apiRequest(SG_APP.cashAccountsEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Idempotency-Key': crypto.randomUUID() }, body: JSON.stringify(payload) }).then(r => { val('sg-cash-account-message').textContent = r.message; load(); window.setTimeout(() => accountDialog.close(), 700); }).catch(err => { val('sg-cash-account-message').textContent = err.message; }).finally(() => { b.disabled = false; });
        });
        val('sg-cash-kind').dispatchEvent(new Event('change'));
        val('sg-cash-transaction-type').addEventListener('change', updatePartyOptions);
        val('sg-cash-transaction-form').addEventListener('submit', e => {
            e.preventDefault(); if (!activeCash || !permissions.has('cash.create')) return;
            const b = e.currentTarget.querySelector('button[type="submit"]'); b.disabled = true; val('sg-cash-transaction-message').textContent = 'Kaydediliyor…';
            const payload = { transaction_type: val('sg-cash-transaction-type').value, party_id: Number(val('sg-cash-party').value || 0), amount: val('sg-cash-transaction-amount').value.trim().replace(',', '.'), transaction_date: val('sg-cash-transaction-date').value, document_no: val('sg-cash-transaction-document').value, description: val('sg-cash-transaction-description').value };
            apiRequest(SG_APP.cashAccountsEndpoint + '/' + activeCash.id + '/transactions', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Idempotency-Key': crypto.randomUUID() }, body: JSON.stringify(payload) }).then(r => { val('sg-cash-transaction-message').textContent = r.message; val('sg-cash-transaction-amount').value = ''; val('sg-cash-transaction-description').value = ''; load().then(() => { const fresh = cashItems.find(x => Number(x.id) === Number(activeCash.id)); if (fresh) openDetail(fresh); }); }).catch(err => { val('sg-cash-transaction-message').textContent = err.message; }).finally(() => { b.disabled = false; });
        });
        const loadTransfers = () => {
            const tbody = val('sg-cash-transfer-rows');
            if (!tbody || !SG_APP.cashTransfersEndpoint) return;
            tbody.replaceChildren();
            const loading = document.createElement('tr'); const loadingCell = document.createElement('td'); loadingCell.colSpan = 6; loadingCell.className = 'sg-table-empty'; loadingCell.textContent = 'Transfer kayıtları yükleniyor…'; loading.appendChild(loadingCell); tbody.appendChild(loading);
            apiRequest(SG_APP.cashTransfersEndpoint).then(data => {
                tbody.replaceChildren();
                if (!data.items || !data.items.length) { const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 6; td.className = 'sg-table-empty'; td.textContent = 'Henüz hesaplar arası transfer kaydı bulunmuyor.'; tr.appendChild(td); tbody.appendChild(tr); return; }
                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    const values = [formatDate(item.transaction_date), item.entry_no, item.from_account_name, item.to_account_name, money(item.amount), item.description || item.document_no || '—'];
                    const labels = ['Tarih', 'İşlem No', 'Kaynak Hesap', 'Hedef Hesap', 'Tutar', 'Açıklama'];
                    values.forEach((value, index) => { const td = document.createElement('td'); td.textContent = value || '—'; td.dataset.label = labels[index]; if (index === 4) td.className = 'sg-transfer-amount'; tr.appendChild(td); });
                    tbody.appendChild(tr);
                });
            }).catch(error => { tbody.replaceChildren(); const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 6; td.className = 'sg-table-empty'; td.textContent = error.message; tr.appendChild(td); tbody.appendChild(tr); });
        };
        val('sg-transfer-add').addEventListener('click', () => {
            if (!permissions.has('cash.transfer') || cashItems.length < 2) { if (cashItems.length < 2) { const hint = val('sg-transfer-availability'); if (hint) { hint.textContent = 'Transfer için en az iki aktif kasa veya banka hesabı gereklidir.'; hint.hidden = false; } } return; }
            val('sg-cash-transfer-form').reset(); val('sg-transfer-date').value = today(); val('sg-transfer-message').textContent = '';
            fillSelect(val('sg-transfer-from'), cashItems, 'Kaynak hesap seçin'); fillSelect(val('sg-transfer-to'), cashItems, 'Hedef hesap seçin');
            transferDialog.showModal();
        });
        val('sg-cash-transfer-close').addEventListener('click', () => transferDialog.close());
        transferDialog.addEventListener('click', e => { if (e.target === transferDialog) transferDialog.close(); });
        val('sg-transfer-date').value = today();
        val('sg-cash-transfer-form').addEventListener('submit', e => {
            e.preventDefault(); if (!permissions.has('cash.transfer')) return;
            if (!val('sg-transfer-from').value || !val('sg-transfer-to').value || val('sg-transfer-from').value === val('sg-transfer-to').value) { val('sg-transfer-message').textContent = 'Kaynak ve hedef hesap farklı olmalıdır.'; return; }
            const b = e.currentTarget.querySelector('button[type="submit"]'); b.disabled = true; val('sg-transfer-message').textContent = 'Transfer kaydediliyor…';
            const payload = { from_account_id: Number(val('sg-transfer-from').value), to_account_id: Number(val('sg-transfer-to').value), amount: val('sg-transfer-amount').value.trim().replace(',', '.'), transaction_date: val('sg-transfer-date').value, document_no: val('sg-transfer-document').value, description: val('sg-transfer-description').value };
            apiRequest(SG_APP.cashTransfersEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Idempotency-Key': crypto.randomUUID() }, body: JSON.stringify(payload) }).then(r => { val('sg-transfer-message').textContent = r.message; val('sg-cash-transfer-form').reset(); transferDialog.close(); load(); loadTransfers(); }).catch(err => { val('sg-transfer-message').textContent = err.message; }).finally(() => { b.disabled = false; });
        });
        updatePartyOptions(); loadParties(); load(); loadTransfers();
    };

    const setupIncomeExpenseModule = (permissions) => {
        const val = (id) => root.querySelector('#' + id);
        const today = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
        const form = val('sg-ie-form');
        if (!form || !SG_APP.incomeExpensesEndpoint || !permissions.has('income_expense.view')) return;
        const money = (v) => new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(Number(v||0));
        const loadOptions = () => apiRequest(SG_APP.incomeExpenseOptionsEndpoint).then(data => {
            const cat=val('sg-ie-category'), cash=val('sg-ie-cash'), type=val('sg-ie-type').value;
            const selected=cat.value; cat.replaceChildren();
            (data.categories||[]).filter(x=>x.category_type===type).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.category_name;cat.appendChild(o);});
            if(selected)cat.value=selected;
            const old=cash.value;cash.replaceChildren();(data.accounts||[]).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=(x.account_kind==='bank'?'Banka · ':'Kasa · ')+x.account_name;cash.appendChild(o);});if(old)cash.value=old;
        });
        const load = () => apiRequest(SG_APP.incomeExpensesEndpoint + (val('sg-ie-filter').value?'?type='+encodeURIComponent(val('sg-ie-filter').value):'')).then(data=>{
            const t=data.totals||{};val('sg-ie-income-total').textContent=money(t.income_total);val('sg-ie-expense-total').textContent=money(t.expense_total);val('sg-ie-net-total').textContent=money(Number(t.income_total||0)-Number(t.expense_total||0));
            const body=val('sg-ie-rows');body.replaceChildren();if(!(data.items||[]).length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=7;td.className='sg-table-empty';td.textContent='Henüz gelir veya gider kaydı yok.';tr.appendChild(td);body.appendChild(tr);return;}
            data.items.forEach(x=>{const tr=document.createElement('tr');[formatDate(x.record_date),x.record_type==='income'?'Gelir':'Gider',x.category_name,x.account_name,x.description||'—',money(x.amount),x.entry_no].forEach((v,i)=>{const td=document.createElement('td');td.textContent=v;if(i===5)td.className=x.record_type==='income'?'sg-job-status sg-job-status-completed':'sg-job-status sg-job-status-cancelled';tr.appendChild(td);});body.appendChild(tr);});
        }).catch(e=>{val('sg-ie-rows').textContent=e.message;});
        val('sg-ie-type').addEventListener('change',loadOptions);val('sg-ie-filter').addEventListener('change',load);
        val('sg-ie-date').value=today();
        form.addEventListener('submit',e=>{e.preventDefault();if(!permissions.has('income_expense.create'))return;const b=form.querySelector('button[type="submit"]');b.disabled=true;val('sg-ie-message').textContent='Kaydediliyor…';const payload={record_type:val('sg-ie-type').value,category_id:Number(val('sg-ie-category').value),cash_account_id:Number(val('sg-ie-cash').value),amount:val('sg-ie-amount').value.trim().replace(',','.'),record_date:val('sg-ie-date').value,document_no:val('sg-ie-document').value,description:val('sg-ie-description').value};apiRequest(SG_APP.incomeExpensesEndpoint,{method:'POST',headers:{'Content-Type':'application/json','Idempotency-Key':crypto.randomUUID()},body:JSON.stringify(payload)}).then(r=>{val('sg-ie-message').textContent=r.message;val('sg-ie-amount').value='';val('sg-ie-document').value='';val('sg-ie-description').value='';load();}).catch(err=>{val('sg-ie-message').textContent=err.message;}).finally(()=>{b.disabled=false;});});
        loadOptions().then(load).catch(e=>{val('sg-ie-message').textContent=e.message;});
    };


    const setupJobsModule = (permissions) => {
        const $ = (id) => root.querySelector('#' + id);
        const form = $('sg-job-form');
        if (!form || !SG_APP.jobsEndpoint || !permissions.has('jobs.view')) return;
        const labels = {new:'Yeni',assigned:'Atandı',in_progress:'Devam ediyor',on_hold:'Beklemede',completed:'Tamamlandı',cancelled:'İptal'};
        const priorityLabels = {low:'Düşük',normal:'Normal',high:'Yüksek',urgent:'Acil'};
        let items = [], people = [], customers = [], activeId = 0;
        let calendarDate = new Date(); let view = 'list';
        const field = (id) => $(id);
        const safeDate = (v) => v ? String(v).slice(0,10) : '';
        const request = (url, options={}) => apiRequest(url, options);
        const loadOptions = () => request(SG_APP.jobOptionsEndpoint).then(d => {
            people=d.personnel||[]; customers=d.customers||[];
            const p=field('sg-job-person'), c=field('sg-job-customer');
            p.replaceChildren(new Option('Atama yok','')); c.replaceChildren(new Option('Bağlantı yok',''));
            people.forEach(x=>p.add(new Option(x.full_name,x.id)));
            customers.forEach(x=>c.add(new Option(x.company_name,x.id)));
        });
        const renderMetrics = () => {
            const open=items.filter(x=>!['completed','cancelled'].includes(x.status));
            field('sg-job-open-count').textContent=open.length;
            field('sg-job-late-count').textContent=open.filter(x=>x.due_date && new Date(x.due_date.replace(' ','T')) < new Date()).length;
            field('sg-job-progress-count').textContent=items.filter(x=>x.status==='in_progress').length;
            field('sg-job-done-count').textContent=items.filter(x=>x.status==='completed').length;
        };
        const renderList = () => {
            const body=field('sg-job-rows'); body.replaceChildren();
            const q=field('sg-job-search').value.trim().toLocaleLowerCase('tr-TR');
            const status=field('sg-job-status-filter').value;
            const rows=items.filter(x=>(!status||x.status===status)&&(!q||(x.title+' '+x.job_no).toLocaleLowerCase('tr-TR').includes(q)));
            if(!rows.length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=7;td.className='sg-table-empty';td.textContent='Bu filtrelerle eşleşen görev bulunamadı.';tr.appendChild(td);body.appendChild(tr);return;}
            rows.forEach(x=>{
                const tr=document.createElement('tr');
                const title=document.createElement('td'); const strong=document.createElement('strong');strong.textContent=x.title;const small=document.createElement('small');small.textContent=x.job_no;title.append(strong,small);tr.appendChild(title);
                [x.company_name||'—',x.assigned_name||'Atanmadı',priorityLabels[x.priority]||x.priority,labels[x.status]||x.status,formatDate(x.due_date)].forEach((v,i)=>{const td=document.createElement('td');td.textContent=v;if(i===3)td.className='sg-job-status sg-job-status-'+x.status;tr.appendChild(td);});
                const action=document.createElement('td');const btn=document.createElement('button');btn.type='button';btn.className='sg-button sg-button-light sg-button-small';btn.textContent='Aç';btn.addEventListener('click',()=>openJob(x.id));action.appendChild(btn);tr.appendChild(action);body.appendChild(tr);
            });
        };

        const renderBoard = () => {
            const board=field('sg-job-board');board.replaceChildren();
            const q=field('sg-job-search').value.trim().toLocaleLowerCase('tr-TR'),status=field('sg-job-status-filter').value;
            const rows=items.filter(x=>(!status||x.status===status)&&(!q||(x.title+' '+x.job_no).toLocaleLowerCase('tr-TR').includes(q)));
            const stages=[['new','Yeni'],['assigned','Atandı'],['in_progress','Devam ediyor'],['on_hold','Beklemede'],['completed','Tamamlandı'],['cancelled','İptal']];
            stages.forEach(([key,label])=>{
                const col=document.createElement('section');col.className='sg-job-board-column';
                const head=document.createElement('h3');head.textContent=label+' · '+rows.filter(x=>x.status===key).length;col.appendChild(head);
                rows.filter(x=>x.status===key).forEach(x=>{
                    const card=document.createElement('button');card.type='button';card.className='sg-job-board-card';
                    const title=document.createElement('strong');title.textContent=x.title;
                    const meta=document.createElement('small');meta.textContent=(x.job_no||'')+' · '+(x.assigned_name||'Atanmadı');
                    const due=document.createElement('span');due.textContent=x.due_date?'Termin: '+formatDate(x.due_date):'Termin yok';
                    card.append(title,meta,due);card.addEventListener('click',()=>openJob(x.id));col.appendChild(card);
                });
                board.appendChild(col);
            });
        };

        const renderCalendar = () => {
            const grid=field('sg-calendar-grid');grid.replaceChildren();
            const y=calendarDate.getFullYear(),m=calendarDate.getMonth();
            field('sg-calendar-title').textContent=new Intl.DateTimeFormat('tr-TR',{month:'long',year:'numeric'}).format(calendarDate);
            ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'].forEach(d=>{const h=document.createElement('div');h.className='sg-calendar-weekday';h.textContent=d;grid.appendChild(h);});
            const first=new Date(y,m,1);const offset=(first.getDay()+6)%7;const start=new Date(y,m,1-offset);
            for(let i=0;i<42;i++){
                const day=new Date(start);day.setDate(start.getDate()+i);
                const cell=document.createElement('div');cell.className='sg-calendar-day'+(day.getMonth()!==m?' is-muted':'');
                const num=document.createElement('span');num.className='sg-calendar-day-number';num.textContent=day.getDate();cell.appendChild(num);
                const key=[day.getFullYear(),String(day.getMonth()+1).padStart(2,'0'),String(day.getDate()).padStart(2,'0')].join('-');
                items.filter(x=>safeDate(x.due_date)===key).slice(0,3).forEach(x=>{const b=document.createElement('button');b.type='button';b.className='sg-calendar-event sg-calendar-event-'+x.priority;b.textContent=x.title;b.title=x.title;b.addEventListener('click',()=>openJob(x.id));cell.appendChild(b);});
                grid.appendChild(cell);
            }
        };
        const load = () => request(SG_APP.jobsEndpoint).then(d=>{items=d.items||[];renderMetrics();renderList();renderBoard();renderCalendar();}).catch(e=>{field('sg-job-rows').textContent=e.message;});
        const resetForm = () => {form.reset();field('sg-job-id').value='';field('sg-job-priority').value='normal';field('sg-job-status').value='new';field('sg-job-message').textContent='';};
        const setReadOnly = (locked) => {
            ['sg-job-title','sg-job-start','sg-job-due','sg-job-person','sg-job-customer','sg-job-priority','sg-job-description'].forEach(id=>field(id).disabled=locked);
            field('sg-job-status').disabled=!permissions.has('jobs.update_status')&&!permissions.has('jobs.assign');
            form.querySelector('button[type="submit"]').hidden=locked&&!permissions.has('jobs.update_status')&&!permissions.has('jobs.assign');
        };
        const openNew = () => {
            activeId=0;resetForm();field('sg-job-dialog-title').textContent='Yeni Görev';field('sg-job-detail').hidden=true;setReadOnly(!permissions.has('jobs.create'));field('sg-job-dialog').showModal();
        };
        const openJob = (id) => {
            activeId=Number(id);request(SG_APP.jobsEndpoint+'/'+activeId).then(d=>{
                const x=d.job;resetForm();field('sg-job-dialog-title').textContent=x.job_no+' · '+x.title;
                field('sg-job-id').value=x.id;field('sg-job-title').value=x.title||'';field('sg-job-description').value=x.description||'';
                field('sg-job-start').value=safeDate(x.start_date);field('sg-job-due').value=safeDate(x.due_date);
                field('sg-job-person').value=x.assigned_personnel_id||'';field('sg-job-customer').value=x.party_id||'';
                field('sg-job-priority').value=x.priority||'normal';field('sg-job-status').value=x.status||'new';
                setReadOnly(!permissions.has('jobs.assign'));
                field('sg-job-detail').hidden=false;
                const notes=field('sg-job-notes');notes.replaceChildren();
                (d.notes||[]).forEach(n=>{const p=document.createElement('p');p.textContent=(n.display_name||'Kullanıcı')+' · '+formatDate(n.created_at)+' — '+n.note;notes.appendChild(p);});
                const history=field('sg-job-history');history.replaceChildren();
                (d.history||[]).slice(0,12).forEach(h=>{const p=document.createElement('p');p.textContent=(h.display_name||'Kullanıcı')+' · '+formatDate(h.created_at)+' — '+(h.action==='note_added'?'Not eklendi':'Görev güncellendi');history.appendChild(p);});
                field('sg-job-note-form').hidden=!permissions.has('jobs.add_note');
                field('sg-job-dialog').showModal();
            }).catch(e=>{window.alert(e.message);});
        };
        field('sg-job-add').addEventListener('click',openNew);
        field('sg-job-close').addEventListener('click',()=>field('sg-job-dialog').close());
        field('sg-job-dialog').addEventListener('click',e=>{if(e.target===field('sg-job-dialog'))field('sg-job-dialog').close();});
        form.addEventListener('submit',e=>{
            e.preventDefault();const id=Number(field('sg-job-id').value||0);
            const payload={title:field('sg-job-title').value.trim(),description:field('sg-job-description').value,start_date:field('sg-job-start').value,due_date:field('sg-job-due').value,assigned_personnel_id:Number(field('sg-job-person').value||0),party_id:Number(field('sg-job-customer').value||0),priority:field('sg-job-priority').value,status:field('sg-job-status').value};
            const button=form.querySelector('button[type="submit"]');button.disabled=true;field('sg-job-message').textContent='Kaydediliyor…';
            request(id?SG_APP.jobsEndpoint+'/'+id:SG_APP.jobsEndpoint,{method:id?'PATCH':'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
            .then(r=>{field('sg-job-message').textContent=r.message||'Kaydedildi.';return load();})
            .then(()=>{if(!id)field('sg-job-dialog').close();}).catch(err=>{field('sg-job-message').textContent=err.message;}).finally(()=>{button.disabled=false;});
        });
        field('sg-job-note-form').addEventListener('submit',e=>{
            e.preventDefault();if(!activeId||!permissions.has('jobs.add_note'))return;
            const note=field('sg-job-note').value.trim();if(!note)return;
            request(SG_APP.jobsEndpoint+'/'+activeId+'/notes',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({note})})
            .then(()=>{field('sg-job-note').value='';openJob(activeId);}).catch(err=>{window.alert(err.message);});
        });
        field('sg-job-search').addEventListener('input',()=>{renderList();renderBoard();});field('sg-job-status-filter').addEventListener('change',()=>{renderList();renderBoard();});
        root.querySelectorAll('[data-job-view]').forEach(b=>b.addEventListener('click',()=>{view=b.dataset.jobView;root.querySelectorAll('[data-job-view]').forEach(x=>x.classList.toggle('is-active',x===b));field('sg-job-calendar').hidden=view!=='calendar';root.querySelector('.sg-job-list-panel').hidden=view!=='list';field('sg-job-board').hidden=view!=='board';}));
        field('sg-calendar-prev').addEventListener('click',()=>{calendarDate.setMonth(calendarDate.getMonth()-1);renderCalendar();});
        field('sg-calendar-next').addEventListener('click',()=>{calendarDate.setMonth(calendarDate.getMonth()+1);renderCalendar();});
        loadOptions().then(load).catch(e=>{field('sg-job-rows').textContent=e.message;});
    };


    const setupPersonnelModule = (permissions) => {
        const rows = root.querySelector('#sg-personnel-rows');
        if (!rows || !SG_APP.personnelEndpoint || !permissions.has('personnel.view')) return;
        const field = (id) => root.querySelector('#' + id);
        const editor = field('sg-personnel-editor');
        const form = field('sg-personnel-form');
        const message = field('sg-personnel-message');
        const fields = {
            full_name: 'sg-personnel-name', user_id: 'sg-personnel-user-id', phone: 'sg-personnel-phone', email: 'sg-personnel-email',
            department: 'sg-personnel-department', job_title: 'sg-personnel-title',
            employment_date: 'sg-personnel-date', status: 'sg-personnel-state', description: 'sg-personnel-description'
        };
        let timer = null;
        const load = () => {
            const params = new URLSearchParams();
            if (field('sg-personnel-search').value.trim()) params.set('search', field('sg-personnel-search').value.trim());
            if (field('sg-personnel-status').value) params.set('status', field('sg-personnel-status').value);
            rows.replaceChildren();
            const loading = document.createElement('tr'); const loadingCell = document.createElement('td');
            loadingCell.colSpan = 8; loadingCell.className = 'sg-table-empty'; loadingCell.textContent = 'Personel kayıtları yükleniyor…'; loading.appendChild(loadingCell); rows.appendChild(loading);
            apiRequest(SG_APP.personnelEndpoint + (params.toString() ? '?' + params.toString() : '')).then((data) => {
                const items = Array.isArray(data.items) ? data.items : [];
                field('sg-personnel-count').textContent = items.length + ' personel kaydı';
                rows.replaceChildren();
                if (!items.length) {
                    const tr = document.createElement('tr'); const td = document.createElement('td');
                    td.colSpan = 8; td.className = 'sg-table-empty'; td.textContent = 'Aramanızla eşleşen personel bulunamadı.';
                    tr.appendChild(td); rows.appendChild(tr); return;
                }
                items.forEach((person) => {
                    const tr = document.createElement('tr');
                    [person.full_name, person.department, person.job_title, person.phone ? formatTurkishPhone(person.phone) : '', person.email, formatDate(person.employment_date)].forEach((value) => {
                        const td = document.createElement('td'); td.textContent = value || '—'; tr.appendChild(td);
                    });
                    const status = document.createElement('td'); status.textContent = person.status === 'active' ? 'Aktif' : 'Pasif';
                    status.className = person.status === 'active' ? 'sg-customer-active' : 'sg-customer-passive'; tr.appendChild(status);
                    const actions = document.createElement('td'); actions.className = 'sg-customer-actions';
                    if (permissions.has('personnel.manage')) {
                        const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'sg-button sg-button-small sg-button-light';
                        edit.textContent = 'Düzenle'; edit.addEventListener('click', () => openEditor(person.id)); actions.appendChild(edit);
                    }
                    tr.appendChild(actions); rows.appendChild(tr);
                });
            }).catch((error) => {
                rows.replaceChildren(); const tr = document.createElement('tr'); const td = document.createElement('td');
                td.colSpan = 8; td.className = 'sg-table-empty'; td.textContent = error.message; tr.appendChild(td); rows.appendChild(tr);
            });
        };
        const reset = () => {
            form.reset(); field('sg-personnel-id').value = ''; field('sg-personnel-state').value = 'active';
            Object.values(fields).forEach((id) => { field(id).disabled = false; });
            field('sg-personnel-editor-title').textContent = 'Yeni Personel'; field('sg-personnel-save').hidden = false; message.textContent = '';
        };
        const openEditor = (id) => apiRequest(SG_APP.personnelEndpoint + '/' + id).then((person) => {
            reset(); field('sg-personnel-id').value = person.id;
            Object.entries(fields).forEach(([key, id]) => { field(id).value = person[key] || (key === 'status' ? 'active' : ''); });
            field('sg-personnel-editor-title').textContent = 'Personel Kartını Düzenle';
            editor.showModal();
        }).catch((error) => window.alert(error.message));
        field('sg-personnel-add').addEventListener('click', () => { reset(); editor.showModal(); });
        field('sg-personnel-cancel').addEventListener('click', () => editor.close());
        editor.addEventListener('click', (event) => { if (event.target === editor) editor.close(); });
        field('sg-personnel-search').addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(load, 250); });
        field('sg-personnel-status').addEventListener('change', load);
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!permissions.has('personnel.manage')) return;
            const id = Number(field('sg-personnel-id').value || 0);
            const payload = {};
            Object.entries(fields).forEach(([key, id]) => { payload[key] = field(id).value; });
            const button = field('sg-personnel-save'); button.disabled = true; message.textContent = 'Kaydediliyor…';
            apiRequest(id ? SG_APP.personnelEndpoint + '/' + id : SG_APP.personnelEndpoint, {
                method: id ? 'PATCH' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            }).then((result) => { message.textContent = result.message || 'Personel kaydedildi.'; return load(); })
                .then(() => editor.close()).catch((error) => { message.textContent = error.message; })
                .finally(() => { button.disabled = false; });
        });
        load();
    };

    const menuBackdrop = root.querySelector('.sg-menu-backdrop');
    const closeMenu = () => {
        if (!sidebar || !toggle) return;
        sidebar.classList.remove('is-open');
        if (menuBackdrop) menuBackdrop.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('sg-menu-open');
    };

    if (toggle && sidebar) {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const opened = !sidebar.classList.contains('is-open');
            sidebar.classList.toggle('is-open', opened);
            if (menuBackdrop) menuBackdrop.classList.toggle('is-visible', opened);
            toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
            document.body.classList.toggle('sg-menu-open', opened);
        });
        if (menuBackdrop) menuBackdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeMenu(); });
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    }

    if (!window.SG_APP || !SG_APP.userEndpoint || !SG_APP.nonce) {
        if (apiState) apiState.textContent = 'Yapılandırma eksik';
        return;
    }

    fetch(SG_APP.userEndpoint, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': SG_APP.nonce, 'Accept': 'application/json' }
    }).then((response) => {
        if (!response.ok) throw new Error('API yanıtı alınamadı');
        return response.json();
    }).then((user) => {
        if (userName) userName.textContent = user.displayName || 'Kullanıcı';
        if (sessionState) sessionState.textContent = 'Aktif';
        if (apiState) apiState.textContent = 'Bağlı';
        return fetch(SG_APP.permissionsEndpoint, {
            method: 'GET', credentials: 'same-origin',
            headers: { 'X-WP-Nonce': SG_APP.nonce, 'Accept': 'application/json' }
        });
    }).then((response) => {
        if (!response || !response.ok) throw new Error('İzin bilgisi alınamadı');
        return response.json();
    }).then((data) => {
        const granted = new Set(data.permissions || []);
        root.querySelectorAll('[data-permission]').forEach((link) => {
            if (!granted.has(link.dataset.permission)) link.hidden = true;
        });
        root.querySelectorAll('.sg-nav-heading').forEach((heading) => {
            let next = heading.nextElementSibling;
            let hasVisible = false;
            while (next && !next.classList.contains('sg-nav-heading')) {
                if (next.matches('a') && !next.hidden) hasVisible = true;
                next = next.nextElementSibling;
            }
            heading.hidden = !hasVisible;
        });
        setupNavigation(granted);
        try { setupCustomerModule(granted); } catch (e) { console.error('Müşteri modülü başlatılamadı:', e); }
        try { setupSupplierModule(granted); } catch (e) { console.error('Tedarikçi modülü başlatılamadı:', e); }
        try { setupAccountsModule(granted); } catch (e) { console.error('Cari hesap modülü başlatılamadı:', e); }
        try { setupCashBankModule(granted); } catch (e) { console.error('Kasa/banka modülü başlatılamadı:', e); }
        try { setupIncomeExpenseModule(granted); } catch (e) { console.error('Gelir/gider modülü başlatılamadı:', e); }
        try { setupPersonnelModule(granted); } catch (e) { console.error('Personel modülü başlatılamadı:', e); }
        try { setupJobsModule(granted); } catch (e) { console.error('İş takibi modülü başlatılamadı:', e); }
        loadDashboard();
        try { setupDashboardCalendar(granted); } catch (e) { console.error('Ana sayfa takvimi başlatılamadı:', e); }
    }).catch(() => {
        if (sessionState) sessionState.textContent = 'Kontrol edilemedi';
        if (apiState) apiState.textContent = 'Bağlantı hatası';
        if (userName) userName.textContent = 'Hesap doğrulanamadı';
    });
})();
