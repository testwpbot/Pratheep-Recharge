-- ============================================================================
-- TMobiling operator logos — phpMyAdmin update
-- ----------------------------------------------------------------------------
-- Run this on your live MySQL database (DirectAdmin > phpMyAdmin > SQL tab).
-- It sets the `logo` column on the TMobiling provider's services so the
-- newly-added operator logos render on the site.
--
-- Safe to run more than once (idempotent). It only touches TMobiling rows,
-- matched by op_code, and never changes profit / pricing / active flags.
-- ============================================================================

-- 1) New / corrected operator logos (the ones that were missing or wrong)
UPDATE services s
JOIN providers p ON p.id = s.provider_id AND p.slug = 'tmobiling'
SET s.logo = CASE s.op_code
    WHEN '11' THEN 'assets/logos/lesipay.png'          -- LESI Pay
    WHEN '40' THEN 'assets/logos/uber.png'             -- Uber Lanka (was ubereats stand-in)
    WHEN '41' THEN 'assets/logos/tripmo.png'           -- Tripmo
    WHEN '25' THEN 'assets/logos/ezcash.png'           -- EzCash Send
    WHEN '27' THEN 'assets/logos/ezcash.png'           -- EzCash Withdraw
    WHEN '33' THEN 'assets/logos/hnbfinance.png'       -- HNB Finance (was hnbassu stand-in)
    WHEN '34' THEN 'assets/logos/janashakthi.png'      -- Janashakthi Life
    WHEN '35' THEN 'assets/logos/allianz.png'          -- Allianz Life
    WHEN '37' THEN 'assets/logos/unionassurance.png'   -- Union Assurance Life
    WHEN '38' THEN 'assets/logos/softlogic.png'        -- Softlogic Life
    WHEN '39' THEN 'assets/logos/visionfund.png'       -- VisionFund Lanka
    WHEN '80' THEN 'assets/logos/fintrex.png'          -- Fintrex Finance
    ELSE s.logo
END
WHERE s.op_code IN ('11','40','41','25','27','33','34','35','37','38','39','80');

-- 2) (Optional) Re-assert the logos that were already correct, in case any
--    TMobiling service row still has a NULL/blank logo in your DB.
UPDATE services s
JOIN providers p ON p.id = s.provider_id AND p.slug = 'tmobiling'
SET s.logo = CASE s.op_code
    WHEN '1'  THEN 'assets/logos/dialog.png'
    WHEN '2'  THEN 'assets/logos/airtel.png'
    WHEN '3'  THEN 'assets/logos/sltmobitel.png'
    WHEN '4'  THEN 'assets/logos/hutch.png'
    WHEN '7'  THEN 'assets/logos/dialog.png'
    WHEN '12' THEN 'assets/logos/dialog.png'
    WHEN '13' THEN 'assets/logos/airtel.png'
    WHEN '14' THEN 'assets/logos/sltmobitel.png'
    WHEN '15' THEN 'assets/logos/hutch.png'
    WHEN '5'  THEN 'assets/logos/dialog.png'
    WHEN '6'  THEN 'assets/logos/dialog.png'
    WHEN '28' THEN 'assets/logos/sltmobitel.png'
    WHEN '16' THEN 'assets/logos/dialog.png'
    WHEN '17' THEN 'assets/logos/dialog.png'
    WHEN '19' THEN 'assets/logos/sltmobitel.png'
    WHEN '10' THEN 'assets/logos/pickme.png'
    WHEN '20' THEN 'assets/logos/sundirect.png'
    WHEN '21' THEN 'assets/logos/d2h.png'
    WHEN '22' THEN 'assets/logos/dishtv.png'
    WHEN '23' THEN 'assets/logos/airtel.png'
    WHEN '79' THEN 'assets/logos/tataplay.png'
    WHEN '29' THEN 'assets/logos/ceb.png'
    WHEN '30' THEN 'assets/logos/leco.png'
    WHEN '31' THEN 'assets/logos/nwsdb.png'
    WHEN '32' THEN 'assets/logos/aia.png'
    WHEN '36' THEN 'assets/logos/srilankains.png'
    WHEN '68' THEN 'assets/logos/hnbassu.png'
    WHEN '9'  THEN 'assets/logos/lankabell.png'
    WHEN '18' THEN 'assets/logos/dialog.png'
    ELSE s.logo
END
WHERE (s.logo IS NULL OR s.logo = '')
  AND s.op_code IN ('1','2','3','4','7','12','13','14','15','5','6','28','16','17','19',
                    '10','20','21','22','23','79','29','30','31','32','36','68','9','18');

-- 3) Check the result
SELECT s.op_code, s.name, s.logo
FROM services s
JOIN providers p ON p.id = s.provider_id AND p.slug = 'tmobiling'
ORDER BY CAST(s.op_code AS UNSIGNED);
