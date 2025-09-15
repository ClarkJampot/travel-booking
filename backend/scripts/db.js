// backend/scripts/db.js
import dotenv from "dotenv";
import { execSync } from "child_process";
import path from "path";
import { fileURLToPath } from "url";

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DB_USER = process.env.DB_USER;
const DB_PASS = process.env.DB_PASS;
const DB_NAME = process.env.DB_NAME;

if (!DB_USER || !DB_PASS || !DB_NAME) {
  console.error("Missing database environment variables in .env");
  process.exit(1);
}

const schemaPath = path.resolve(__dirname, "../../database/schema.sql");
const seedPath = path.resolve(__dirname, "../../database/seed.sql");

function run(command) {
  console.log(`> ${command}`);
  execSync(command, { stdio: "inherit" });
}

function init() {
  run(`mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} < ${schemaPath}`);
}

function seed() {
  run(`mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} < ${seedPath}`);
}

function reset() {
  run(
    `mysql -u${DB_USER} -p${DB_PASS} -e "DROP DATABASE IF EXISTS ${DB_NAME}; CREATE DATABASE ${DB_NAME};"`,
  );
  init();
}

const cmd = process.argv[2];

switch (cmd) {
  case "init":
    init();
    break;
  case "seed":
    seed();
    break;
  case "reset":
    reset();
    break;
  case "reset:seed":
    reset();
    seed();
    break;
  default:
    console.log("Usage: node scripts/db.js [init|seed|reset|reset:seed]");
}
