async function loadFeaturedDestinations() {
  try {
    const data = await apiCall('/destinations?limit=4');
    const container = document.getElementById('featuredDestinations');
    
    if (data.results.length === 0) {
      container.innerHTML = renderEmptyState({ message: 'Currently no featured destinations available.' });
      return;
    }
    
    container.querySelectorAll('.reveal').forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
    
    container.innerHTML = data.results.map((dest, index) => {
      return renderDestinationCard(dest, index, 'col-md-3');
    }).join('');
  } catch (error) {
    console.error('Error loading destinations:', error);
  }
}

async function loadTopHotels() {
  try {
    const data = await apiCall('/top/hotels?limit=4');
    const container = document.getElementById('topHotels');
    
    if (data.results.length === 0) {
      container.innerHTML = renderEmptyState({ message: 'Currently no hotels available.' });
      return;
    }
    
    container.querySelectorAll('.reveal').forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
    
    container.innerHTML = data.results.map((hotel, index) => {
      return renderHotelCard(hotel, index, 0, 'col-md-3');
    }).join('');
  } catch (error) {
    console.error('Error loading hotels:', error);
  }
}

async function loadTopActivities() {
  try {
    const data = await apiCall('/top/activities?limit=4');
    const container = document.getElementById('topActivities');
    
    if (data.results.length === 0) {
      container.innerHTML = renderEmptyState({ message: 'Currently no activities available.' });
      return;
    }
    
    container.querySelectorAll('.reveal').forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
    
    container.innerHTML = data.results.map((activity, index) => {
      return renderCard({ type: 'activity', data: activity, columnsClass: 'col-md-3' });
    }).join('');
  } catch (error) {
    console.error('Error loading activities:', error);
  }
}

document.addEventListener('DOMContentLoaded', function() {
  loadLayout();
  
  if (typeof initSearchBar === 'function') {
    initSearchBar();
  }
  
  loadFeaturedDestinations().then(() => observeRevealElements());
  loadTopHotels().then(() => observeRevealElements());
  loadTopActivities().then(() => observeRevealElements());
});

