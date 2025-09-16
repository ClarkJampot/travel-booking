import express from "express";
import { query } from "../models/db.js";

const router = express.Router();

router.get("/", async (req, res) => {
  try {
    const hotels = await query("SELECT * FROM hotels");
    res.json(hotels);
  } catch (err) {
    console.error("Error fetching hotels: ", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

// POST /api/hotels
router.post("/", async (req, res) => {
  try {
    const { name, city, country, price_per_night, rating, created_by } = req.body || {};
    if (!name || !city || !country || price_per_night == null) {
      return res.status(400).json({ error: "Missing required fields" });
    }
    const price = Number(price_per_night);
    if (!Number.isFinite(price) || price < 0) {
      return res.status(400).json({ error: "Invalid price_per_night" });
    }
    const rate = rating == null ? null : Number(rating);
    if (rate != null && (!Number.isFinite(rate) || rate < 0 || rate > 5)) {
      return res.status(400).json({ error: "Invalid rating" });
    }

    const result = await query(
      "INSERT INTO hotels (name, city, country, price_per_night, rating, created_by) VALUES (?, ?, ?, ?, ?, ?)",
      [name, city, country, price, rate, created_by || null],
    );
    const rows = await query("SELECT * FROM hotels WHERE id = ?", [result.insertId]);
    res.status(201).json(rows[0]);
  } catch (err) {
    console.error("Error creating hotel:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;
