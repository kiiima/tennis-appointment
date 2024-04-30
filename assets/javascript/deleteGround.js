export function deleteGround(idGround)
{
  // Koristimo encodeURIComponent da bismo enkodovali ID u URL format
  var encodedId = encodeURIComponent(idGround);
  // Koristimo Symfony path funkciju da bismo generisali URL
  var url = "{{ path('app_delete_ground', {'idGround': 'IDGROUND'}) }}".replace('IDGROUND', encodedId);
  // Redirekcija na URL
  window.location.href = url;
}