window.onload = function(){
  var elFb = document.getElementById('enquete6');
  var snsName = document.querySelector('input[name=snsName]');

  if(snsName.value=="" || snsName.value != 'facebook'){
    elFb.style.display="none";
  }else{
    elFb.style.display="block";
  }
}