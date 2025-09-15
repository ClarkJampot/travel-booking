import express from "express";
import { query } from "../models/db.js";

const router = express.Router();

// GET /api/flights?origin=&destination=&date=&minPrice=&maxPrice=&page=&limit=
router.get("/", async (req, res) => {
  try {
    const { origin, destination, date, minPrice, maxPrice } = req.query;
    const page = parseInt(req.query.page || "1", 10);
    const limit = Math.min(parseInt(req.query.limit || "10", 10), 50);
    const offset = (page - 1) * limit;

    const filters = [];
    const params = [];

    if (origin) {
      filters.push("origin = ?");
      params.push(origin);
    }
    if (destination) {
      filters.push("destination = ?");
      params.push(destination);
    }
    if (date) {
      filters.push("depart_date = ?");
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

    const flights = await query(
      `SELECT * FROM flights ${where} ORDER BY depart_date ASC, id ASC LIMIT ${limit} OFFSET ${offset}`,
      params,
    );

    res.json({ page, limit, results: flights });
  } catch (err) {
    console.error("Error fetching flights:", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;




