(function(){
  if (window.__cc_reg_init__) return; // guard against double include/bind
  window.__cc_reg_init__ = true;

  function q(s,root){ return (root||document).querySelector(s); }
  function qa(s,root){ return Array.from((root||document).querySelectorAll(s)); }

  function init(){
    var typeSel = q('#reg-type');
    if(!typeSel) return; // not on registration page

    var team = q('#team-section');
    var open = q('#open-section');
    var teamList = q('#writer-list');
    var addTeam = q('#add-writer');
    var openList = q('#open-writer-list');
    var addOpen = q('#add-open-writer');

    var teamBase=100, teamIncluded=7, teamExtra=10, openPrice=20;

    function toggleRequired(isTeam){
      var tName = q('[name="team_name"]'), tSchool=q('[name="school"]'), tEmail=q('[name="guardian_email"]');
      [tName,tSchool,tEmail].forEach(function(el){ if(el){ el.required = !!isTeam; } });
      qa('input[name="open_names[]"]', openList).forEach(function(el){ el.required = !isTeam; });
      qa('input[name="open_emails[]"]', openList).forEach(function(el){ el.required = !isTeam; });
      qa('input[name="open_phones[]"]', openList).forEach(function(el){ el.required = !isTeam; });
    }

    function showMode(){
      var isTeam = (typeSel.value === 'team');
      if(team) team.style.display = isTeam ? 'block' : 'none';
      if(open) open.style.display = isTeam ? 'none' : 'block';
      toggleRequired(isTeam);
      calcTeam(); calcOpen();
    }

    function calcTeam(){
      if(!teamList) return;
      var count = qa('input[name="writers[]"]', teamList).length;
      var extra = Math.max(0, count - teamIncluded);
      var total = teamBase + extra*teamExtra;
      var el = q('#team-total-fee'); if(el) el.textContent = '$'+total;
    }

    function calcOpen(){
      if(!openList) return;
      var count = qa('.open-writer', openList).length;
      var total = count * openPrice;
      var el = q('#open-total-fee'); if(el) el.textContent = '$'+total;
    }

    if(addTeam && !addTeam.__bound){
      addTeam.__bound = true;
      addTeam.addEventListener('click', function(e){
        e.preventDefault();
        var d = document.createElement('div');
        d.className = 'row';
        d.innerHTML = '<input name="writers[]" placeholder="Writer full name">';
        teamList.appendChild(d);
        calcTeam();
      });
    }

    if(addOpen && !addOpen.__bound){
      addOpen.__bound = true;
      addOpen.addEventListener('click', function(e){
        e.preventDefault();
        var d = document.createElement('div');
        d.className = 'open-writer card';
        d.style.marginTop = '8px';
        d.innerHTML = '<div class="row">'
          + '<div><label class="required">Name</label><input name="open_names[]"></div>'
          + '<div><label class="required">Email</label><input type="email" name="open_emails[]"></div>'
          + '<div><label class="required">Phone</label><input name="open_phones[]"></div>'
          + '</div>';
        openList.appendChild(d);
        toggleRequired(false);
        calcOpen();
      });
      // seed one row if empty
      if(openList && qa('.open-writer', openList).length===0){ addOpen.click(); }
    }

    // seed team with 7 rows if empty
    if(teamList && qa('input[name="writers[]"]', teamList).length===0){
      for(var i=0;i<7;i++){ var d=document.createElement('div'); d.className='row'; d.innerHTML='<input name="writers[]" placeholder="Writer full name">'; teamList.appendChild(d); }
    }

    typeSel.addEventListener('change', showMode, {once:false});
    showMode();
  }

  if(document.readyState === 'complete' || document.readyState === 'interactive'){
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init, {once:true});
  }
})();
