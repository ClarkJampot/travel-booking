import express from "express";
import hotels from "./hotels.js";

const router = express.Router();

router.use("/hotels", hotels);

// test endpoint
// router.get("/ping", (req, res) => {
//   res.json({ message: "Test" });
// });

export default router;
