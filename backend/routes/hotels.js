import express from "express";
import { query } from "../models/db.js";

const router = express.Router();

router.get("/", async (req, res) => {
  try {
    const hotels = await query("SELECT * FROM HOTELS");
    res.json(hotels);
  } catch (err) {
    console.error("Error fetching hotels: ", err);
    res.status(500).json({ error: "Internal server error" });
  }
});

export default router;
