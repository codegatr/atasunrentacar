/* ATA SU Admin JS */
(function(){
  'use strict';

  // Silme onayi
  document.querySelectorAll('[data-onay]').forEach(function(el){
    el.addEventListener('click', function(e){
      if (!confirm(el.dataset.onay || 'Bu islemi yapmak istediginize emin misiniz?')) {
        e.preventDefault();
        return false;
      }
    });
  });

  // Slug otomatik
  var baslikInp = document.querySelector('[data-slug-kaynak]');
  var slugInp = document.querySelector('[data-slug-hedef]');
  if (baslikInp && slugInp) {
    baslikInp.addEventListener('input', function(){
      if (slugInp.dataset.elle === '1') return;
      var s = baslikInp.value.toLowerCase()
        .replace(/ç/g,'c').replace(/ğ/g,'g').replace(/ı/g,'i')
        .replace(/ö/g,'o').replace(/ş/g,'s').replace(/ü/g,'u')
        .replace(/[^a-z0-9\s-]/g,'')
        .replace(/\s+/g,'-')
        .replace(/-+/g,'-')
        .replace(/^-|-$/g,'');
      slugInp.value = s;
    });
    slugInp.addEventListener('input', function(){ slugInp.dataset.elle = '1'; });
  }

  // Sekmeler
  document.querySelectorAll('.sekmeler').forEach(function(grup){
    var sekmeler = grup.querySelectorAll('.sekme');
    sekmeler.forEach(function(sekme){
      sekme.addEventListener('click', function(){
        var hedef = sekme.dataset.hedef;
        sekmeler.forEach(function(s){ s.classList.remove('aktif'); });
        sekme.classList.add('aktif');
        document.querySelectorAll('.sekme-icerik').forEach(function(i){ i.style.display = 'none'; });
        var icerik = document.querySelector('.sekme-icerik[data-icerik="' + hedef + '"]');
        if (icerik) icerik.style.display = 'block';
      });
    });
  });

  // Iade tarihi >= alis tarihi
  document.addEventListener('change', function(e){
    if (e.target.matches('[name="alis_tarihi"]')) {
      var iade = document.querySelector('[name="iade_tarihi"]');
      if (iade) {
        var d = new Date(e.target.value);
        if (!isNaN(d)) {
          d.setDate(d.getDate() + 1);
          iade.min = d.toISOString().slice(0, 10);
        }
      }
    }
  });
})();
