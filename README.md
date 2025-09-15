# Travel Booking Website

A simplified Agoda/Klook-style travel booking system for academic purposes.  
This project allows users to browse and book hotels, flights, activities, and transfers.  
Includes role-based access (Customer, Owner, Admin) and a basic admin panel.

---

## 🚀 Features (Planned)
- Book Hotels, Flights, Activities, and Land Transfers
- Role-based system: Customer, Owner, Admin
- Admin panel for managing listings and bookings
- Documentation (Technical, User, Design)

---

## 📂 Project Structure
```
travel-booking/
├─ backend/                 # Express server, routes, DB access
│  ├─ models/
│  │  └─ db.js
│  ├─ routes/
│  │  ├─ index.js
│  │  └─ hotels.js
│  ├─ scripts/
│  │  └─ db.js
│  ├─ package.json
│  └─ server.js
├─ public/                  # Static frontend assets
│  ├─ index.html
│  └─ js/
│     └─ main.js
├─ docs/
│  └─ PROJECT_INDEX.md
├─ LICENSE
└─ README.md
```


---

## ⚙️ Requirements
- **Node.js** >= 18
- **MySQL** >= 8
- **Browser**: Chrome, Firefox, Edge, or Safari

---

## 🛠️ Installation
Clone the repository:
```bash
git clone https://github.com/ClarkJampot/travel-booking.git
cd travel-booking/backend
npm install
```

Run the server:
```bash
npm start
```

Open `http://localhost:3000` in your browser. API base is `http://localhost:3000/api`.

For a full overview, see the project index: `docs/PROJECT_INDEX.md`.
