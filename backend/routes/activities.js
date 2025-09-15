import express from "express";
import { query } from "../models/db.js";

const router = express.Router();

// GET /api/activities?city=&date=&minPrice=&maxPrice=&page=&limit=
router.get("/", async (req, res) => {
  try {
    const { city, date, minPrice, maxPrice } = req.query;
    const page = parseInt(req.query.page || "1", 10);
    const limit = Math.min(parseInt(req.query.limit || "10", 10), 50);
    const offset = (page - 1) * limit;

    const filters = [];
    const params = [];

    if (city) {
      filters.push("city = ?");
      params.push(city);
    }
    if (date) {
      filters.push("date = ?");
      params.push(date);
    }
    if (minPrice) {
      filters.push("price >= ?");
      params.push(Number(minPrice));
    }
    if (maxPrice) {
      filters.push("price <= ?");
      params.push(Number(maxPrice));
    }

    const where = filters.length ? `WHERE ${filters.join(" AND ")}` : "";

    const rows = await query(
      `SELECT * FROM activities ${where} ORDER BY date ASC, id ASC LIMIT ${limit} OFFSET ${offset}`,
      params,
    );

    res.json({ page, limit, results: rows });
  } catch (err) {
    console.error("Error fetching activities:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;




