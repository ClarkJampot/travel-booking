#!/usr/bin/env node
import { execSync } from "child_process";

const { DB_USER, DB_PASS, DB_NAME } = process.env;
const mode = process.argv[2];

if (!DB_USER || !DB_PASS || !DB_NAME) {
  console.error("Missing DB_USER, DB_PASS, or DB_NAME in environment.");
  process.exit(1);
}

try {
  if (mode !== "--init") {
    console.log("Dropping and recreating database...");
    execSync(
      `mysql -u${DB_USER} -p${DB_PASS} -e "DROP DATABASE IF EXISTS \\\`${DB_NAME}\\\`; CREATE DATABASE \\\`${DB_NAME}\\\`;"`,
    );
  } else {
    console.log("Initializing schema on existing database...");
  }

  console.log("Importing schema...");
  execSync(
    `mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} < ../database/schema.sql`,
    { stdio: "inherit", shell: true },
  );

  console.log(
    "Database",
    mode === "--init" ? "initialized" : "reset",
    "successfully.",
  );
} catch (err) {
  console.error("Error:", err.message);
  process.exit(1);
}
