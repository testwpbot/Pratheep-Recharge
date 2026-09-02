-- ============================================================================
-- Topup Mart DTH — activate the DTH operators (phpMyAdmin update)
-- ----------------------------------------------------------------------------
-- Run this on your live MySQL database (DirectAdmin > phpMyAdmin > SQL tab).
--
-- WHY: DTH recharges are routed through Topup Mart (op codes 120-124). Those
-- rows were seeded as inactive placeholders, so the plans page would not show
-- them / would fall back to another provider. This turns them ON so customers
-- can buy DTH and orders go to Topup Mart.
--
-- Topup Mart DTH operator codes (confirmed by the business):
--   120 = Airtel DTH      121 = DishTV        122 = Sun Direct
--   123 = Tata Sky/Play   124 = Videocon d2h
--
-- Safe to run more than once (idempotent). It only touches Topup Mart DTH rows
-- matched by op_code, and never changes profit / pricing.
-- ============================================================================

-- 1) Make sure the Topup Mart provider itself is active.
UPDATE providers SET is_active = 1 WHERE slug = 'topup-mart';

-- 2) Activate the Topup Mart DTH operators and fix their names/logos.
UPDATE services s
JOIN providers p ON p.id = s.provider_id AND p.slug = 'topup-mart'
SET
    s.is_active = 1,
    s.type      = 'dth',
    s.name = CASE s.op_code
        WHEN '120' THEN 'Airtel Digital TV'
        WHEN '121' THEN 'Dish TV'
        WHEN '122' THEN 'Sun Direct'
        WHEN '123' THEN 'Tata Play'
        WHEN '124' THEN 'Videocon d2h'
        ELSE s.name
    END,
    s.logo = CASE s.op_code
        WHEN '120' THEN 'assets/logos/airtel.png'
        WHEN '121' THEN 'assets/logos/dishtv.png'
        WHEN '122' THEN 'assets/logos/sundirect.png'
        WHEN '123' THEN 'assets/logos/tataplay.png'
        WHEN '124' THEN 'assets/logos/d2h.png'
        ELSE s.logo
    END,
    s.category_id = (SELECT id FROM categories WHERE slug = 'dth' LIMIT 1)
WHERE s.op_code IN ('120','121','122','123','124');

-- 3) OPTIONAL — if you do NOT want the TMobiling DTH rows (op 20/21/22/23/79)
--    to compete with Topup Mart, hide them so DTH always goes to Topup Mart.
--    Uncomment the next statement to run it.
--
-- UPDATE services s
-- JOIN providers p ON p.id = s.provider_id AND p.slug = 'tmobiling'
-- SET s.is_active = 0
-- WHERE s.op_code IN ('20','21','22','23','79');

-- 4) Verify — DTH services that customers will see.
SELECT p.slug AS provider, s.op_code, s.name, s.type, s.is_active
FROM services s
JOIN providers p ON p.id = s.provider_id
JOIN categories c ON c.id = s.category_id AND c.slug = 'dth'
ORDER BY p.slug, CAST(s.op_code AS UNSIGNED);
