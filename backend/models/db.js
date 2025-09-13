const mysql = require("mysql2");

const pool = mysql.createPool({
  host: process.env.DB_HOST || "localhost",
  user: process.env.DB_USER || "travel_user",
  password: process.env.DB_PASS || "",
  database: process.env.DB_NAME || "travel_booking",
});

module.exports = pool.promise();
