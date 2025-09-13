document.getElementById("searchForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const res = await fetch("/api/ping");
  const data = await res.json();
  alert(data.message);
});
