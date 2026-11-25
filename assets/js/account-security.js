(function () {
'use strict';
if (!window.SG_APP || !SG_APP.restRoot || !SG_APP.nonce) return;
const chip = document.getElementById('sg-user-name');
if (!chip || document.getElementById('sg-account-menu')) return;
const topbarRight = chip.parentElement;
const menu = document.createElement('div');
menu.id = 'sg-account-menu';
menu.className = 'sg-account-menu';
menu.hidden = true;
menu.innerHTML = '<button type="button" class="sg-account-menu-item" id="sg-password-open">Şifre Değiştir</button><a class="sg-account-menu-item sg-account-menu-logout" href="' + (chip.dataset.logoutUrl || '') + '">Güvenli Çıkış</a>';
chip.setAttribute('role', 'button');
chip.setAttribute('tabindex', '0');
chip.setAttribute('aria-haspopup', 'true');
chip.setAttribute('aria-expanded', 'false');
chip.classList.add('sg-user-chip-trigger');
topbarRight.appendChild(menu);
const toggleMenu = () => {
 const expanded = chip.getAttribute('aria-expanded') === 'true';
 chip.setAttribute('aria-expanded', String(!expanded));
 menu.hidden = expanded;
};
chip.addEventListener('click', toggleMenu);
chip.addEventListener('keydown', (event) => {
 if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggleMenu(); }
});
document.addEventListener('click', (event) => {
 if (!topbarRight.contains(event.target)) { menu.hidden = true; chip.setAttribute('aria-expanded', 'false'); }
});
document.addEventListener('keydown', (event) => {
 if (event.key === 'Escape') { menu.hidden = true; chip.setAttribute('aria-expanded', 'false'); }
});
const dialog = document.createElement('dialog');
dialog.className = 'sg-modal sg-password-dialog';
dialog.innerHTML = '<div class="sg-panel-heading"><div><p class="sg-card-kicker">HESAP GÜVENLİĞİ</p><h2>Şifremi Değiştir</h2></div><button type="button" class="sg-button sg-button-light" data-close>Kapat</button></div><form id="sg-password-form" class="sg-customer-form" autocomplete="off"><label class="sg-field-wide"><span>Mevcut şifre *</span><input id="sg-current-password" type="password" autocomplete="current-password" required></label><label><span>Yeni şifre *</span><input id="sg-new-password" type="password" autocomplete="new-password" required></label><label><span>Yeni şifre (tekrar) *</span><input id="sg-confirm-password" type="password" autocomplete="new-password" required></label><p class="sg-password-rules sg-field-wide">En az 8 karakter; büyük harf, küçük harf ve rakam içermelidir.</p><div class="sg-password-actions sg-field-wide"><button type="button" class="sg-button sg-button-light" id="sg-password-generate">Güçlü Şifre Oluştur</button><button type="submit" class="sg-button sg-button-primary" id="sg-password-save">Şifreyi Güncelle</button></div><p class="sg-password-message sg-field-wide" id="sg-password-message" role="status" aria-live="polite"></p></form>';
document.body.appendChild(dialog);
const el = (id) => dialog.querySelector('#' + id);
const form = el('sg-password-form');
const message = el('sg-password-message');
const close = () => { dialog.close(); form.reset(); message.textContent = ''; };
el('sg-password-open').addEventListener('click', () => { menu.hidden = true; chip.setAttribute('aria-expanded', 'false'); dialog.showModal(); });
dialog.querySelector('[data-close]').addEventListener('click', close);
dialog.addEventListener('click', (event) => { if (event.target === dialog) close(); });
const valid = (value) => value.length >= 8 && /[A-Z]/.test(value) && /[a-z]/.test(value) && /[0-9]/.test(value);
el('sg-password-generate').addEventListener('click', () => {
 const upper='ABCDEFGHJKLMNPQRSTUVWXYZ', lower='abcdefghijkmnopqrstuvwxyz', digits='23456789', extras='!@#$%*-_';
 const pick=(set)=>set[Math.floor(Math.random()*set.length)];
 const chars=[pick(upper),pick(lower),pick(digits)];
 const all=upper+lower+digits+extras;
 while(chars.length<16) chars.push(pick(all));
 for(let i=chars.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[chars[i],chars[j]]=[chars[j],chars[i]];}
 const value=chars.join('');
 el('sg-new-password').value=value; el('sg-confirm-password').value=value;
 el('sg-new-password').type='text'; el('sg-confirm-password').type='text';
 message.textContent='Güçlü şifre oluşturuldu. Kaydetmeden önce güvenli bir yere not edin.';
 message.className='sg-password-message sg-field-wide is-success';
});
form.addEventListener('submit', async (event) => {
 event.preventDefault();
 const currentPassword=el('sg-current-password').value, newPassword=el('sg-new-password').value;
 if(!valid(newPassword)){message.textContent='Şifre en az 8 karakter olmalı; büyük harf, küçük harf ve rakam içermelidir.';message.className='sg-password-message sg-field-wide is-error';return;}
 if(newPassword!==el('sg-confirm-password').value){message.textContent='Yeni şifreler eşleşmiyor.';message.className='sg-password-message sg-field-wide is-error';return;}
 const save=el('sg-password-save');save.disabled=true;message.textContent='Şifre güncelleniyor…';
 try{
  const response=await fetch(SG_APP.restRoot+'me/password',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':SG_APP.nonce,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({currentPassword,newPassword})});
  const result=await response.json();if(!response.ok)throw new Error(result.message||'Şifre güncellenemedi.');
  message.textContent=result.message||'Şifreniz değiştirildi.';message.className='sg-password-message sg-field-wide is-success';
  el('sg-current-password').value='';el('sg-new-password').value='';el('sg-confirm-password').value='';
 }catch(error){message.textContent=error.message;message.className='sg-password-message sg-field-wide is-error';}
 finally{save.disabled=false;}
});
})();