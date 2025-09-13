import express from "express";

const router = express.Router();

// test endpoint
router.get("/ping", (req, res) => {
  res.json({ message: "Test" });
});

export default router;
